<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Repository;

use AppBundle\Entity\Device;
use AppBundle\Entity\DeviceInterface;

class DeviceInterfaceRepository extends BaseRepository
{
    public function getCount(): int
    {
        return $this->createQueryBuilder('di')
            ->select('COUNT(di)')
            ->andWhere('di.deletedAt IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Full defined namespace, because magic.
     */
    public function removeInterfaceByInternalId(
        Device $device,
        array $activeInterfaceIdInternal
    ): array {
        $removedInterfaces = [];

        $qb = $this->createQueryBuilder('i')
            ->select('i')
            ->where('i.device = :device')
            ->andWhere('i.internalId NOT IN (:internalId)')
            ->andWhere('i.deletedAt IS NULL')
            ->setParameter('device', $device)
            ->setParameter('internalId', $activeInterfaceIdInternal);

        $deviceInterfaces = $qb->getQuery()->getResult();

        /** @var DeviceInterface $deviceInterface */
        foreach ($deviceInterfaces as $deviceInterface) {
            $removedInterfaces[] = $deviceInterface->getName();
            $deviceInterface->setDeletedAt(new \DateTime());

            foreach ($deviceInterface->getInterfaceIps() as $ip) {
                $this->getEntityManager()->remove($ip);
            }
        }

        return $removedInterfaces;
    }

    public function findByMac(string $mac): ?DeviceInterface
    {
        $qb = $this->createQueryBuilder('di')
            ->join('di.device', 'd')
            ->where('di.macAddress = :mac')
            ->andWhere('di.deletedAt IS NULL')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('mac', $mac)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
