<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Command;

use AppBundle\Entity\DeviceLog;
use AppBundle\Sync\DeviceConnectionFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SearchServiceDeviceCommand extends NetworkDeviceCommand
{
    use BaseCommandTrait;

    protected function configure()
    {
        $this->setName('crm:search:serviceDevice')
            ->setDescription('Search service device.')
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
        if (! $this->device->getVendor()) {
            return;
        }

        try {
            $deviceConnection = $this->container->get(DeviceConnectionFactory::class)->create($this->device);

            $deviceConnection->readGeneralInformation();
            $deviceConnection->saveStatistics();

            $message = $this->translator->trans('Device synchronized.');
            $this->log($message, DeviceLog::STATUS_OK);
            $this->device->setLastSuccessfulSynchronization(new \DateTime());
        } catch (\Exception $e) {
            $this->logException($e);
        }

        $this->device->setLastSynchronization(new \DateTime());
    }
}
