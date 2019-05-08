<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Sync;

use AppBundle\Entity\Device;
use AppBundle\Entity\General;
use AppBundle\Entity\Service;
use Doctrine\ORM\EntityManager;

class SynchronizationManager
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function unsynchronizeService(Service $service): int
    {
        $countDevices = 0;

        foreach ($service->getServiceDevices() as $serviceDevice) {
            if (! $serviceDevice->getInterface() || ! $serviceDevice->getInterface()->getDevice()) {
                continue;
            }

            $device = $serviceDevice->getInterface()->getDevice();
            if ($device->getSynchronized()) {
                ++$countDevices;
                $device->setSynchronized(false);
            }
        }

        return $countDevices;
    }

    public function unsynchronizeAllDevices(): int
    {
        $countDevices = 0;

        $devices = $this->em->getRepository(Device::class)->findAll();
        foreach ($devices as $device) {
            if ($device->getSynchronized()) {
                ++$countDevices;
                $device->setSynchronized(false);
            }
        }

        return $countDevices;
    }

    /**
     * @todo This should be handled by OptionsFacade, however every single use of this method crashes because of
     *       the flush. Created a task (UCRM-810) to move this to subscriber.
     */
    public function unsynchronizeSuspend()
    {
        $general = $this->em->getRepository(General::class)->findOneBy(
            [
                'code' => General::SUSPEND_SYNCHRONIZED,
            ]
        );

        if ($general && $general->getValue()) {
            $general->setValue('0');
        }
    }
}
