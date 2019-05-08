<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Command;

use AppBundle\Entity\Device;
use AppBundle\Entity\DeviceLog;
use AppBundle\Entity\Option;
use AppBundle\Sync\DeviceConnectionFactory;
use AppBundle\Sync\Exceptions\InvalidServerIpException;
use AppBundle\Sync\Exceptions\QoSSyncNotSupportedException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncDeviceCommand extends NetworkDeviceCommand
{
    use BaseCommandTrait;

    protected function configure(): void
    {
        $this->setName('crm:sync:device')
            ->setDescription('Synchronize device.')
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'Device ID is required!'
            )
            ->addOption('force');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->init();

        $this->deviceId = (int) $input->getArgument('id');
        $this->findDevice();

        if ($this->canSynchronize() || $input->getOption('force')) {
            $this->syncDevice();
        } elseif ($this->device) {
            $this->log('Device already synchronized, skipped.', DeviceLog::STATUS_WARNING);
        }

        return 0;
    }

    /**
     * Sync single device.
     * Get all IP´s from suspended services and add this list to device.
     */
    private function syncDevice(): void
    {
        $serverIp = $this->options->get(Option::SERVER_IP);
        $serverSuspendPort = $this->options->get(Option::SERVER_SUSPEND_PORT);

        try {
            $deviceConnection = $this->container->get(DeviceConnectionFactory::class)->create($this->device);

            try {
                if (! $this->device->isQosSynchronized()) {
                    $this->log('Synchronizing QoS.', DeviceLog::STATUS_OK);
                    $deviceConnection->syncQos();
                    $this->log('QoS synchronized.', DeviceLog::STATUS_OK);
                }
            } catch (\OutOfRangeException $e) {
                $this->log('QoS rules cannot be synchronized. IPs limit is exceeded.', DeviceLog::STATUS_ERROR);
            } catch (QoSSyncNotSupportedException $e) {
                $this->log(
                    sprintf(
                        'QoS rules cannot be synchronized. %s QoS synchronization is only possible for %s.',
                        $e->getSystem(),
                        $e->getExpected()
                    ),
                    DeviceLog::STATUS_ERROR
                );
            }

            $hasValidServerIp = false !== filter_var($serverIp, FILTER_VALIDATE_IP);
            if ($this->device->isSuspendEnabled()) {
                $this->log('Synchronizing suspension.', DeviceLog::STATUS_OK);
                if (! $hasValidServerIp) {
                    throw new InvalidServerIpException();
                }

                $blockedIpList = $this->getBlockedIpList($deviceConnection::SET_DEFAULT_NETMASK);
                $deviceConnection->syncBlockedList($blockedIpList);
                $deviceConnection->syncNatRules($serverIp, $serverSuspendPort);
                $deviceConnection->syncFilterRules($serverIp);
                $this->log('Suspension synchronized.', DeviceLog::STATUS_OK);
            }

            $message = $this->translator->trans('Device synchronized.');
            $this->log($message, DeviceLog::STATUS_OK);
            $this->device->setLastSuccessfulSynchronization(new \DateTime());
        } catch (\Exception $e) {
            $this->logException($e);
        }

        $this->device->setSynchronized(true);
        $this->device->setQosSynchronized(true);
        $this->device->setLastSynchronization(new \DateTime());

        $this->em->flush();
    }

    private function getBlockedIpList(bool $setDefaultNetmask): array
    {
        $blockedIpList = [];

        if (! $this->options->get(Option::SUSPEND_ENABLED)) {
            return $blockedIpList;
        }

        $blockedIps = $this->em->getRepository(Device::class)->findBlockedServiceIps();
        foreach ($blockedIps as $ip) {
            if (null === $ip['netmask']) {
                for ($i = $ip['firstIp']; $i <= $ip['lastIp']; ++$i) {
                    $blockedIpList[] = $setDefaultNetmask
                        ? sprintf('%s/%d', long2ip($i), 32)
                        : long2ip($i);
                }
            } else {
                $blockedIpList[] = $ip['netmask'] === 32
                    ? long2ip($ip['firstIp'])
                    : sprintf('%s/%d', long2ip($ip['firstIp']), $ip['netmask']);
            }
        }

        return $blockedIpList;
    }

    private function canSynchronize(): bool
    {
        $syncSuspend = $this->device->isSuspendEnabled()
            && (! $this->device->getSynchronized()
                || $this->device->getLastSynchronization() <= (new \DateTime())->modify('-1 hour'));
        $syncQos = ! $this->device->isQosSynchronized();

        return
            $this->device
            && ($syncSuspend || $syncQos);
    }
}
