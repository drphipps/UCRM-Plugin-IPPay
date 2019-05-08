<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use ApiBundle\Request\RefundCollectionRequest;
use AppBundle\Database\UtcDateTimeType;
use AppBundle\Entity\Client;
use AppBundle\Entity\Refund;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class RefundDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getGridModel(Client $client = null): QueryBuilder
    {
        $qb = $this->entityManager->getRepository(Refund::class)
            ->createQueryBuilder('r')
            ->addSelect('r.method as r_method, r.amount AS r_amount')
            ->addSelect('o.id as o_id, o.name as o_name, c, u, cur')
            ->addSelect('client_full_name(c, u) AS c_fullname')
            ->leftJoin('r.client', 'c')
            ->leftJoin('c.user', 'u')
            ->leftJoin('r.currency', 'cur')
            ->leftJoin('c.organization', 'o')
            ->groupBy('r.id, c.id, u.id, cur.id, o.id');

        if ($client) {
            $qb->andWhere('r.client = :clientId')
                ->setParameter('clientId', $client->getId());
        }

        return $qb;
    }

    /**
     * @return Refund[]
     *
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function getCollection(RefundCollectionRequest $request): array
    {
        $qb = $this->entityManager->getRepository(Refund::class)->createQueryBuilder('refund');

        $criteria = new Criteria();
        if ($request->clientId) {
            $criteria->andWhere(Criteria::expr()->eq('client', $request->clientId));
        }

        if ($request->startDate) {
            $qb->andWhere('refund.createdDate >= :startDate');
            $qb->setParameter('startDate', $request->startDate, UtcDateTimeType::NAME);
        }

        if ($request->endDate) {
            $qb->andWhere('refund.createdDate <= :endDate');
            $qb->setParameter('endDate', $request->endDate, UtcDateTimeType::NAME);
        }

        if ($request->currency) {
            $criteria->andWhere(Criteria::expr()->eq('currency', $request->currency));
        }

        if ($request->limit !== null) {
            $criteria->setMaxResults($request->limit);
        }

        if ($request->offset !== null) {
            $criteria->setFirstResult($request->offset);
        }

        $orderBy = [
            $request->order ?: 'createdDate' => $request->direction ?: 'ASC',
        ];

        $criteria->orderBy(
            array_merge(
                $orderBy,
                [
                    'id' => $request->direction ?: 'ASC',
                ]
            )
        );

        $qb->addCriteria($criteria);

        return $qb->getQuery()->getResult();
    }
}
