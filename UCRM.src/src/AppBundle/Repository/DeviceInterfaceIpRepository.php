<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Repository;

use AppBundle\Entity\Device;
use AppBundle\Entity\DeviceInterfaceIp;
use Doctrine\ORM\NoResultException;

class DeviceInterfaceIpRepository extends BaseRepository
{
    public function getIpsForRemovalByInternalId(Device $device, array $activeIpIdInternal): array
    {
        return $this->createQueryBuilder('i')
            ->select('i')
            ->innerJoin('i.interface', 'di')
            ->where('di.device = :device')
            ->andWhere('i.internalId NOT IN (:internalId)')
            ->setParameter('device', $device)
            ->setParameter('internalId', $activeIpIdInternal)
            ->getQuery()->getResult();
    }

    /**
     * @param array|DeviceInterfaceIp[] $ips
     *
     * @todo move this to facade
     */
    public function removeIps(array $ips): array
    {
        $removedIps = [];
        $em = $this->getEntityManager();

        foreach ($ips as $ip) {
            $ipRange = $ip->getIpRange();
            $removedIps[long2ip($ipRange->getIpAddress())] = $ipRange->getNetmask();
            $ip->getInterface()->removeInterfaceIp($ip);
            $em->remove($ip);
        }

        return $removedIps;
    }

    public function getLastAccessibleIp(Device $device): int
    {
        $qb = $this->createQueryBuilder('i')
            ->select('i.ipRange.ipAddress')
            ->innerJoin('i.interface', 'di')
            ->where('di.device = :device')
            ->andWhere('i.wasLastConnectionSuccessful = TRUE')
            ->setMaxResults(1)
            ->setParameter('device', $device);

        try {
            return $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
            return 0;
        }
    }

    public function findByIpAddress(int $ipAddress, ?int $netmask = null): ?DeviceInterfaceIp
    {
        $qb = $this->createQueryBuilder('i')
            ->select('i')
            ->join('i.interface', 'di')
            ->join('di.device', 'd')
            ->where('i.ipRange.ipAddress = :ipAddress')
            ->andWhere('di.deletedAt IS NULL')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('ipAddress', $ipAddress)
            ->setMaxResults(1);

        if (null !== $netmask) {
            $qb->andWhere('i.ipRange.netmask = :netmask')
                ->setParameter('netmask', $netmask);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
