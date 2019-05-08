<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Repository;

class ClientLogsViewRepository extends BaseRepository
{
    public function getByIds(array $ids): array
    {
        if (! $ids) {
            return [];
        }

        $qb = $this->createQueryBuilder('clv')
            ->where('clv.id IN (:ids)')
            ->setParameter('ids', $ids);

        return $qb->getQuery()->getResult();
    }
}
