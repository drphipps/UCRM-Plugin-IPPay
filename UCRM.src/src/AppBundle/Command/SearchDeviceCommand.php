<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Command;

use AppBundle\Entity\Device;
use AppBundle\Entity\DeviceLog;
use AppBundle\Event\Device\DeviceUpdateIndexEvent;
use AppBundle\Sync\DeviceConnectionFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SearchDeviceCommand extends NetworkDeviceCommand
{
    use BaseCommandTrait;

    /**
     * @var Device
     */
    protected $device;

    protected function configure()
    {
        $this->setName('crm:search:device')
            ->setDescription('Search device.')
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'Device ID is required!'
            )
            ->addOption(
                'option',
                null,
                InputOption::VALUE_NONE,
                ''
            );
    }

    /**
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->init();

        $this->deviceId = (int) $input->getArgument('id');
        $this->findDevice();

        if ($this->device && $this->device->getLoginUsername()) {
            $this->searchDevice();

            if ($this->em->isOpen()) {
                $this->em->flush();
            }
        }

        return 0;
    }

    private function searchDevice()
    {
        try {
            $deviceConnection = $this->container->get(DeviceConnectionFactory::class)->create($this->device);

            $deviceConnection
                ->readGeneralInformation()
                ->searchForInterfaces()
                ->searchForWirelessInterfaces()
                ->searchForWirelessSecurity()
                ->searchForInterfaceIpsChangedInterface()
                ->searchForInterfaceIps()
                ->searchForUnknownConnectedDevices()
                ->saveConfiguration()
                ->saveStatistics()
                ->removeEmptyInterfaces();

            if ($this->device instanceof Device) {
                $this->container->get(EventDispatcherInterface::class)->dispatch(
                    DeviceUpdateIndexEvent::class,
                    new DeviceUpdateIndexEvent($this->device)
                );
            }

            $deviceConnection->log('Device synchronized.', DeviceLog::STATUS_OK);
            $this->device->setLastSuccessfulSynchronization(new \DateTime());
        } catch (\Exception $e) {
            $this->logException($e);
        }

        $this->device->setLastSynchronization(new \DateTime());

        if (null !== $this->device->getSearchIp() && count($this->device->getDeviceIps()) > 1) {
            $this->em->remove($this->device->getSearchIp());
        }
    }
}
