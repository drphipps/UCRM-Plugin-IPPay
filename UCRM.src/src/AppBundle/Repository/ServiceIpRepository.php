<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Repository;

class ServiceIpRepository extends BaseRepository
{
    public function getOverlappingRangesCount(int $firstIp, int $lastIp, ?int $ipId = null): int
    {
        $qb = $this
            ->createQueryBuilder('sip')
            ->select('COUNT(sip.id)')
            ->join('sip.serviceDevice', 'sd')
            ->andWhere('sip.ipRange.firstIp <= :lastIp')
            ->andWhere('sip.ipRange.lastIp >= :firstIp')
            ->andWhere('sd.service IS NOT NULL')
            ->setParameter('lastIp', $lastIp)
            ->setParameter('firstIp', $firstIp);

        if ($ipId) {
            $qb
                ->andWhere('sip.id != :ipId')
                ->setParameter('ipId', $ipId);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
