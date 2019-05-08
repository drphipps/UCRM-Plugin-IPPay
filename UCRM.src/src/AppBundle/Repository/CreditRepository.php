<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Repository;

class CreditRepository extends BaseRepository
{
    public function getCount(): int
    {
        $qb = $this->createQueryBuilder('credit')
            ->resetDQLPart('orderBy')
            ->select('COUNT(credit.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
