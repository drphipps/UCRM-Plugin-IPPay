<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Repository;

use Doctrine\ORM\QueryBuilder;

class ReportDataUsageRepository extends BaseRepository
{
    public function getTrafficOverviewQueryBuilder(\DateTimeImmutable $today): QueryBuilder
    {
        return $this
            ->createQueryBuilder('r')
            ->addSelect('r.currentPeriodDownload + r.currentPeriodUpload AS current_total')
            ->addSelect('(COALESCE(r.lastPeriodDownload, 0) + COALESCE(r.lastPeriodUpload, 0)) AS last_total')
            ->addSelect('client_full_name(c, u) AS c_fullname')
            ->addSelect('t.dataUsageLimit AS t_dataUsageLimit')
            ->addSelect('t.dataUsageLimit * 1073741824 AS t_dataUsageLimitByte') // GiB to B
            ->join('r.service', 's')
            ->join('s.tariff', 't')
            ->join('s.client', 'c')
            ->join('c.user', 'u')
            ->where('r.reportCreated = :created')
            ->andWhere('u.deletedAt IS NULL')
            ->andWhere('s.deletedAt IS NULL')
            ->setParameter('created', $today->format('Y-m-d'));
    }
}
