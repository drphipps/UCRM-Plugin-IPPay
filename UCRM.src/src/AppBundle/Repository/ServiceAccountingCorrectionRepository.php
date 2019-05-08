<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Repository;

use AppBundle\Entity\Service;

class ServiceAccountingCorrectionRepository extends BaseRepository
{
    public function getChartData(\DateTimeImmutable $since, ?Service $service = null): array
    {
        $qb = $this->createQueryBuilder('ac');
        $qb
            ->select('ac.date, SUM(ac.upload) AS upload, SUM(ac.download) AS download')
            ->andWhere('ac.date >= :since')
            ->orderBy('ac.date')
            ->groupBy('ac.date')
            ->setParameter('since', $since->format('Y-m-d'));

        if ($service) {
            $qb->andWhere('ac.service = :service')->setParameter('service', $service);
        }

        return $qb->getQuery()->getResult();
    }

    public function hasData(\DateTimeImmutable $since, Service $service): bool
    {
        return (bool) $this->createQueryBuilder('ac')
            ->select('1')
            ->andWhere('ac.date >= :since')
            ->andWhere('ac.service = :service')
            ->setParameter('since', $since->format('Y-m-d'))
            ->setParameter('service', $service)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
