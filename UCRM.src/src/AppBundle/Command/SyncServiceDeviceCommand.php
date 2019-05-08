<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Command;

use AppBundle\Entity\DeviceLog;
use AppBundle\Sync\DeviceConnectionFactory;
use AppBundle\Sync\Exceptions\QoSSyncNotSupportedException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncServiceDeviceCommand extends NetworkDeviceCommand
{
    use BaseCommandTrait;

    protected function configure()
    {
        $this->setName('crm:sync:serviceDevice')
            ->setDescription('Synchronize service device.')
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'Service device ID is required!'
            );
    }

    /**
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->init();

        $this->deviceId = (int) $input->getArgument('id');
        $this->findServiceDevice();

        if ($this->canSynchronize()) {
            $this->syncDevice();
        }

        return 0;
    }

    private function syncDevice()
    {
        try {
            $deviceConnection = $this->container->get(DeviceConnectionFactory::class)->create($this->device);
            $deviceConnection->syncQos();

            $message = $this->translator->trans('Device synchronized.');
            $this->log($message, DeviceLog::STATUS_OK);
            $this->device->setLastSuccessfulSynchronization(new \DateTime());
        } catch (QoSSyncNotSupportedException $e) {
            $this->log(
                sprintf(
                    'QoS rules cannot be synchronized. %s QoS synchronization is only possible for %s.',
                    $e->getSystem(),
                    $e->getExpected()
                ),
                DeviceLog::STATUS_ERROR
            );
        } catch (\Exception $e) {
            $this->logException($e);
        }

        $this->device->setLastSynchronization(new \DateTime());
        $this->em->flush();
    }

    private function canSynchronize(): bool
    {
        return
            $this->device
            && $this->device->getLoginUsername()
            && (
                ! $this->device->isQosSynchronized()
                || $this->device->getLastSynchronization() <= (new \DateTime())->modify('-1 hour')
            );
    }
}
