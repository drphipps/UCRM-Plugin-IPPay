<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Repository;

use AppBundle\Entity\Service;

class ServiceAccountingRepository extends BaseRepository
{
    public function getChartData(\DateTimeImmutable $since, ?Service $service = null): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb
            ->select('a.date, SUM(a.upload) AS upload, SUM(a.download) AS download')
            ->andWhere('a.date >= :since')
            ->orderBy('a.date')
            ->groupBy('a.date')
            ->setParameter('since', $since->format('Y-m-d'));

        if ($service) {
            $qb->andWhere('a.service = :service')->setParameter('service', $service);
        }

        return $qb->getQuery()->getResult();
    }

    public function getDataForService(Service $service, ?\DateTimeImmutable $since = null, ?\DateTimeImmutable $to = null): array
    {
        $qb = $this->createQueryBuilder('sa');
        $qb
            ->select('SUM(sa.upload) AS upload, SUM(sa.download) AS download')
            ->where('sa.service = :service')
            ->groupBy('sa.service')
            ->setParameter('service', $service);

        if ($since) {
            $qb
                ->andWhere('sa.date >= :since')
                ->setParameter('since', $since->format('Y-m-d'));
        }
        if ($to) {
            $qb
                ->andWhere('sa.date <= :to')
                ->setParameter('to', $to->format('Y-m-d'));
        }

        return $qb->getQuery()->getArrayResult();
    }

    public function hasData(\DateTimeImmutable $since, Service $service): bool
    {
        return (bool) $this->createQueryBuilder('a')
            ->select('1')
            ->andWhere('a.date >= :since')
            ->andWhere('a.service = :service')
            ->setParameter('since', $since->format('Y-m-d'))
            ->setParameter('service', $service)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
