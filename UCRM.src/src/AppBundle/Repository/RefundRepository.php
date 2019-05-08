<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;

class RefundRepository extends BaseRepository
{
    public function getTotalAmount(int $clientId): float
    {
        $qb = $this->createQueryBuilder('r')
            ->select('SUM(r.amount)')
            ->where('r.client = :clientId')
            ->setParameter('clientId', $clientId);

        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    public function getCount(): int
    {
        $qb = $this->getQueryBuilder()
            ->resetDQLPart('orderBy')
            ->select('COUNT(refund.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function getQueryBuilder(
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null,
        ?int $limit = null,
        ?int $offset = null
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('refund');
        $criteria = Criteria::create();

        if ($startDate) {
            $criteria->andWhere(Criteria::expr()->gte('createdDate', $startDate));
        }

        if ($endDate) {
            $criteria->andWhere(Criteria::expr()->lte('createdDate', $endDate));
        }

        if ($offset > 0) {
            $criteria->setFirstResult($offset);
        }

        if ($limit > 0) {
            $criteria->setMaxResults($limit);
        }

        $criteria->orderBy(
            [
                'createdDate' => 'ASC',
                'id' => 'ASC',
            ]
        );

        $qb->addCriteria($criteria);

        return $qb;
    }
}
