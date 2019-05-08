<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\Device;
use AppBundle\Entity\DeviceInterface;
use AppBundle\Entity\DeviceInterfaceIp;
use AppBundle\Entity\SearchDeviceQueue;
use AppBundle\Entity\Vendor;
use AppBundle\Sync\Exceptions\LoginException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class DeviceFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getAllDevices(
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $repository = $this->entityManager->getRepository(Device::class);

        return $repository->findBy(['deletedAt' => null], ['id' => 'ASC'], $limit, $offset);
    }

    /**
     * @throws LoginException
     */
    public function handleAddToSyncQueue(Device $device): bool
    {
        if (! $this->addToSyncQueue($device)) {
            return false;
        }

        $this->entityManager->flush();

        return true;
    }

    /**
     * @return array [$synced, $alreadySynced, $failed]
     */
    public function handleAddToSyncQueueMultiple(array $ids): array
    {
        $devices = $this->entityManager->getRepository(Device::class)->findBy(
            [
                'id' => $ids,
                'deletedAt' => null,
            ]
        );

        $count = count($devices);
        $synced = 0;
        $errors = 0;

        foreach ($devices as $device) {
            try {
                if (! $this->addToSyncQueue($device)) {
                    continue;
                }

                ++$synced;
            } catch (LoginException $e) {
                ++$errors;
            }
        }

        if ($synced > 0) {
            $this->entityManager->flush();
        }

        return [$synced, $count - $synced - $errors, $errors];
    }

    public function handleDelete(Device $device): bool
    {
        if (! $this->setDeleted($device)) {
            return false;
        }

        $this->entityManager->flush();

        return true;
    }

    /**
     * @return array [$deleted, $failed]
     */
    public function handleDeleteMultiple(array $ids): array
    {
        $devices = $this->entityManager->getRepository(Device::class)->findBy(
            [
                'id' => $ids,
                'deletedAt' => null,
            ]
        );

        $count = count($devices);
        $deleted = 0;

        foreach ($devices as $device) {
            if (! $this->setDeleted($device)) {
                continue;
            }

            ++$deleted;
        }

        if ($deleted > 0) {
            $this->entityManager->flush();
        }

        return [$deleted, $count - $deleted];
    }

    public function getGridModel(): QueryBuilder
    {
        return $this->entityManager->getRepository(Device::class)
            ->createQueryBuilder('d')
            ->addSelect('d.status AS d_status')
            ->join('d.site', 's')
            ->join('d.vendor', 'v')
            ->andWhere('d.deletedAt IS NULL')
            ->andWhere('s.deletedAt IS NULL')
            ->groupBy('d.id, s.id, v.id');
    }

    public function getNetFlowGridModel(): QueryBuilder
    {
        return $this->entityManager->getRepository(Device::class)
            ->createQueryBuilder('d')
            ->select('d, s')
            ->join('d.site', 's')
            ->andWhere('d.deletedAt IS NULL')
            ->andWhere('s.deletedAt IS NULL')
            ->andWhere(
                '
                    d.netFlowActiveVersion IS NOT NULL
                    OR d.netFlowLog IS NOT NULL
                    OR d.netFlowSynchronized = false
                '
            )
            ->andWhere('d.vendor IN (:vendors)')
            ->setParameter('vendors', Vendor::SYNCHRONIZED_VENDORS);
    }

    private function setDeleted(Device $device): bool
    {
        if ($device->isDeleted()) {
            return false;
        }

        foreach ($device->getParents() as $parent) {
            $device->removeParent($parent);
        }
        foreach ($device->getChildren() as $child) {
            $child->removeParent($device);
        }

        $searchDeviceQueue = $this->entityManager->getRepository(SearchDeviceQueue::class)->find($device);
        if (null !== $searchDeviceQueue) {
            $this->entityManager->remove($searchDeviceQueue);
        }

        foreach ($device->getNotDeletedInterfaces() as $deviceInterface) {
            foreach ($deviceInterface->getInterfaceIps() as $ip) {
                $this->entityManager->remove($ip);
            }

            $deviceInterface->setDeletedAt(new \DateTime());
        }

        if (null !== $device->getSearchIp()) {
            $this->entityManager->remove($device->getSearchIp());
        }

        $device->setDeletedAt(new \DateTime());

        return true;
    }

    /**
     * @throws LoginException
     */
    private function addToSyncQueue(Device $device): bool
    {
        if (null === $device->getLoginUsername()) {
            throw new LoginException('Device has incorrect credentials.');
        }

        if (
            ! $device->getSearchIp() &&
            ! $device->getManagementIpAddress() &&
            ! $this->hasAccessibleInterface($device)
        ) {
            throw new LoginException('Device doesn\'t have an accessible IP address.');
        }

        $alreadyInQueue = $this->entityManager->getRepository(SearchDeviceQueue::class)->find($device);
        if ($alreadyInQueue) {
            return false;
        }

        $searchDeviceQueue = new SearchDeviceQueue();
        $searchDeviceQueue->setDevice($device);
        $this->entityManager->persist($searchDeviceQueue);

        return true;
    }

    private function hasAccessibleInterface(Device $device): bool
    {
        return (bool) $device
            ->getNotDeletedInterfaces()
            ->filter(
                function (DeviceInterface $interface) {
                    $accessibleIp = $interface->getInterfaceIps()->filter(
                        function (DeviceInterfaceIp $ip) {
                            return $ip->getIsAccessible();
                        }
                    );

                    return $accessibleIp->count() > 0;
                }
            )
            ->count();
    }
}
