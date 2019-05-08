<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Repository;

use AppBundle\Entity\Service;

class TaxRepository extends BaseRepository
{
    public function getCountOfSelected(): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(1)')
            ->andWhere('t.deletedAt IS NULL')
            ->andWhere('t.selected = TRUE');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function existsAny(): bool
    {
        return (bool) $this->createQueryBuilder('t')
            ->select('1')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array|null
     */
    public function getSelected()
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.deletedAt IS NULL')
            ->andWhere('t.selected = TRUE');

        return $qb->getQuery()->getResult();
    }

    public function getAvailableTaxesForService(Service $service): array
    {
        $serviceTaxes = array_filter(
            [
                $service->getTax1(),
                $service->getTax2(),
                $service->getTax3(),
            ]
        );

        $qb = $this->createQueryBuilder('t')
            ->select('t')
            ->andWhere('t.deletedAt IS NULL');

        if (! empty($serviceTaxes)) {
            $qb->andWhere('t.id NOT IN (:taxes)')
                ->setParameter('taxes', $serviceTaxes);
        }

        return $qb->getQuery()->getResult();
    }

    public function getTaxesData(): array
    {
        return $this->createQueryBuilder('t', 't.id')
            ->select('t.id, t.name, t.rate')
            ->orderBy('t.id')
            ->getQuery()
            ->getArrayResult();
    }
}
