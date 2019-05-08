<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 *
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Repository;

use AppBundle\Database\UtcDateTimeType;
use AppBundle\Entity\Service;
use AppBundle\Entity\Vendor;
use Doctrine\ORM\Query\Expr\Join;

class DeviceRepository extends BaseRepository
{
    public function getAccessibleDevices(): array
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.interfaces', 'i', Join::WITH, 'i.deletedAt IS NULL')
            ->leftJoin('i.interfaceIps', 'iip')
            ->where('iip.isAccessible = TRUE OR d.managementIpAddress IS NOT NULL')
            ->andWhere('d.loginUsername IS NOT NULL')
            ->andWhere('d.vendor IN (:vendors)')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('vendors', Vendor::SYNCHRONIZED_VENDORS);

        return $qb->getQuery()->getResult();
    }

    public function getAccessibleSuspendDevices(): array
    {
        $qb = $this->createQueryBuilder('d')
            ->select('d')
            ->leftJoin('d.interfaces', 'i', Join::WITH, 'i.deletedAt IS NULL')
            ->leftJoin('i.interfaceIps', 'iip')
            ->where('iip.isAccessible = TRUE OR d.managementIpAddress IS NOT NULL')
            ->andWhere('d.loginUsername IS NOT NULL')
            ->andWhere('d.vendor IN (:vendors)')
            ->andWhere('d.isSuspendEnabled = TRUE')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('vendors', Vendor::SYNCHRONIZED_VENDORS);

        return $qb->getQuery()->getResult();
    }

    public function findBlockedServiceIps(): array
    {
        $qb = $this->createQueryBuilder('d')
            ->select('ips.ipRange.ipAddress AS ip')
            ->addSelect('ips.ipRange.netmask AS netmask')
            ->addSelect('ips.ipRange.firstIp AS firstIp')
            ->addSelect('ips.ipRange.lastIp AS lastIp')
            ->join('d.interfaces', 'i')
            ->join('i.serviceDevices', 'sd')
            ->join('sd.service', 's')
            ->join('sd.serviceIps', 'ips')
            ->leftJoin('s.stopReason', 'sr')
            ->andWhere('s.deletedAt IS NULL')
            ->andWhere(
                '(
                    sr.id IS NOT NULL
                    AND (s.suspendedFrom IS NULL OR s.suspendedFrom <= :suspendedFrom)
                )
                OR (s.status = :ended AND s.supersededByService IS NULL)'
            )
            ->setParameter('ended', Service::STATUS_ENDED)
            ->setParameter('suspendedFrom', new \DateTime(), UtcDateTimeType::NAME);

        return $qb->getQuery()->getArrayResult();
    }

    public function getNetFlowUnsynchronizedDevices(): array
    {
        $qb = $this->createQueryBuilder('d')
            ->select('d')
            ->andWhere('d.vendor IN (:vendors)')
            ->andWhere('d.netFlowSynchronized = FALSE')
            ->setParameter('vendors', Vendor::SYNCHRONIZED_VENDORS);

        return $qb->getQuery()->getResult();
    }

    public function getAccessibleUnsynchronizedNetworkDevices(): array
    {
        $qb = $this->createQueryBuilder('d')
            ->select('d')
            ->leftJoin('d.interfaces', 'i', Join::WITH, 'i.deletedAt IS NULL')
            ->leftJoin('i.interfaceIps', 'iip')
            ->where('iip.isAccessible = TRUE OR d.managementIpAddress IS NOT NULL')
            ->andWhere('d.qosSynchronized = FALSE')
            ->andWhere('d.loginUsername IS NOT NULL')
            ->andWhere('d.vendor IN (:vendors)')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('vendors', Vendor::SYNCHRONIZED_VENDORS);

        return $qb->getQuery()->getResult();
    }
}
