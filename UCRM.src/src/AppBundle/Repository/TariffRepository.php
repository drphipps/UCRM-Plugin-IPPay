<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Repository;

use Doctrine\ORM\QueryBuilder;

class TariffRepository extends BaseRepository
{
    public function getCount(): int
    {
        return $this->createQueryBuilder('t')
            ->select('COUNT(t)')
            ->andWhere('t.deletedAt IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function existsTariffWithEnabledSetupFee(): bool
    {
        return (bool) $this->createExistsQueryBuilder()
            ->andWhere('t.setupFee IS NOT NULL')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function existsTariffWithEnabledEarlyTerminationFee(): bool
    {
        return (bool) $this->createExistsQueryBuilder()
            ->andWhere('t.earlyTerminationFee IS NOT NULL')
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function createExistsQueryBuilder(): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t')
            ->select('1')
            ->andWhere('t.deletedAt IS NULL')
            ->setMaxResults(1);

        return $qb;
    }
}
