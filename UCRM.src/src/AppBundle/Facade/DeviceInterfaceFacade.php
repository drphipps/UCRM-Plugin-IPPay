<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Component\Sync\SynchronizationManager;
use AppBundle\Entity\DeviceInterface;
use AppBundle\Entity\DeviceInterfaceIp;
use AppBundle\Event\DeviceInterface\DeviceInterfaceAddEvent;
use AppBundle\Event\DeviceInterface\DeviceInterfaceArchiveEvent;
use AppBundle\Event\DeviceInterface\DeviceInterfaceEditEvent;
use AppBundle\Event\DeviceInterfaceIp\DeviceInterfaceIpAddEvent;
use AppBundle\Event\DeviceInterfaceIp\DeviceInterfaceIpDeleteEvent;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use TransactionEventsBundle\TransactionDispatcher;

class DeviceInterfaceFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    /**
     * @var SynchronizationManager
     */
    private $synchronizationManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        TransactionDispatcher $transactionDispatcher,
        SynchronizationManager $synchronizationManager
    ) {
        $this->entityManager = $entityManager;
        $this->transactionDispatcher = $transactionDispatcher;
        $this->synchronizationManager = $synchronizationManager;
    }

    public function handleCreate(DeviceInterface $deviceInterface): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($deviceInterface) {
                $this->entityManager->persist($deviceInterface);
                yield new DeviceInterfaceAddEvent($deviceInterface);
            }
        );
    }

    public function handleUpdate(DeviceInterface $deviceInterface, DeviceInterface $deviceInterfaceBeforeUpdate): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($deviceInterface, $deviceInterfaceBeforeUpdate) {
                $ips = new ArrayCollection();
                foreach ($deviceInterfaceBeforeUpdate->getInterfaceIps() as $ip) {
                    $ips->add($ip);
                }

                $interfaceIps = $deviceInterface->getInterfaceIps();
                $device = $deviceInterface->getDevice();
                foreach ($ips as $item) {
                    if ($interfaceIps->contains($item) === false) {
                        $device->setSynchronized(false);
                        $this->synchronizationManager->unsynchronizeSuspend();

                        $this->entityManager->remove($item);
                    }
                }

                yield new DeviceInterfaceEditEvent($deviceInterface, $deviceInterfaceBeforeUpdate);
            }
        );
    }

    public function handleArchive(DeviceInterface $deviceInterface): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($deviceInterface) {
                foreach ($deviceInterface->getInterfaceIps() as $ip) {
                    $this->entityManager->remove($ip);
                }

                $deviceInterface->setDeletedAt(new \DateTime());
                yield new DeviceInterfaceArchiveEvent($deviceInterface);
            }
        );
    }

    public function handleCreateIp(DeviceInterfaceIp $deviceInterfaceIp): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($deviceInterfaceIp) {
                $device = $deviceInterfaceIp->getInterface()->getDevice();
                $device->setSynchronized(false);

                $this->synchronizationManager->unsynchronizeSuspend();

                $this->entityManager->persist($deviceInterfaceIp);

                yield new DeviceInterfaceIpAddEvent($deviceInterfaceIp);
            }
        );
    }

    public function handleDeleteIp(DeviceInterfaceIp $deviceInterfaceIp): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($deviceInterfaceIp) {
                $id = $deviceInterfaceIp->getId();
                $this->entityManager->remove($deviceInterfaceIp);
                yield new DeviceInterfaceIpDeleteEvent($deviceInterfaceIp, $id);
            }
        );
    }
}
