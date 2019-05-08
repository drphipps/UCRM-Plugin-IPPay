<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use ApiBundle\Request\PaymentPlanCollectionRequest;
use AppBundle\Entity\PaymentPlan;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;

class PaymentPlanDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return PaymentPlan[]
     */
    public function getCollection(PaymentPlanCollectionRequest $request): array
    {
        $qb = $this->entityManager->getRepository(PaymentPlan::class)->createQueryBuilder('paymentPlan');

        $criteria = new Criteria();
        if ($request->clientId) {
            $criteria->andWhere(Criteria::expr()->eq('client', $request->clientId));
        }

        if ($request->startDate) {
            $criteria->andWhere(Criteria::expr()->gte('createdDate', $request->startDate));
        }

        if ($request->endDate) {
            $criteria->andWhere(Criteria::expr()->lte('createdDate', $request->endDate));
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
