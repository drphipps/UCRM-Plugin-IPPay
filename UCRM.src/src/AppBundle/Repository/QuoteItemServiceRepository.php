<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Repository;

use AppBundle\Entity\Service;

class QuoteItemServiceRepository extends BaseRepository
{
    public function hasQuote(Service $service, ?array $statuses = null): bool
    {
        $qb = $this->createQueryBuilder('qis')
            ->select('1')
            ->andWhere('qis.service = :service OR qis.originalService = :service')
            ->setParameter('service', $service)
            ->setMaxResults(1);

        if ($statuses) {
            $qb
                ->join('qis.quote', 'q')
                ->andWhere('q.status IN (:statuses)')
                ->setParameter('statuses', $statuses);
        }

        return (bool) $qb->getQuery()->getOneOrNullResult();
    }
}
