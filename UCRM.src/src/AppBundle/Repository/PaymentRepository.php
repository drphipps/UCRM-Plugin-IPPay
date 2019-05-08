<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Repository;

use ApiBundle\Request\PaymentCollectionRequest;
use AppBundle\Database\UtcDateTimeType;
use AppBundle\Entity\Client;
use AppBundle\Entity\Payment;
use AppBundle\Entity\PaymentCover;
use AppBundle\Entity\Refund;
use AppBundle\Util\Arrays;
use Doctrine\ORM\QueryBuilder;

class PaymentRepository extends BaseRepository
{
    public function getClientTotalPaid(int $clientId): float
    {
        $qb = $this->createQueryBuilder('p')
            ->select('SUM(p.amount)')
            ->where('p.client = :clientId')
            ->setParameter('clientId', $clientId);

        $refundAmount = $this->getEntityManager()->getRepository(Refund::class)->getTotalAmount($clientId);

        return $qb->getQuery()->getSingleScalarResult() - $refundAmount;
    }

    public function getCount(): int
    {
        $qb = $this->getQueryBuilder(new PaymentCollectionRequest())
            ->select('COUNT(payment)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function getQueryBuilder(PaymentCollectionRequest $request): QueryBuilder
    {
        $qb = $this->createQueryBuilder('payment');

        if ($request->clientId) {
            $qb->andWhere('payment.client = :client')
                ->setParameter('client', $request->clientId);
        }

        if ($request->startDate) {
            $qb->andWhere('payment.createdDate >= :startDate')
                ->setParameter('startDate', $request->startDate, UtcDateTimeType::NAME);
        }

        if ($request->endDate) {
            $qb->andWhere('payment.createdDate <= :endDate')
                ->setParameter('endDate', $request->endDate, UtcDateTimeType::NAME);
        }

        if ($request->currency) {
            $qb->andWhere('payment.currency = :currency')
                ->setParameter('currency', $request->currency);
        }

        if ($request->order) {
            $qb->addOrderBy(sprintf('payment.%s', $request->order), $request->direction ?: 'ASC');
            if ($request->order !== 'id') {
                $qb->addOrderBy('payment.id', $request->direction ?: 'ASC');
            }
        }

        if ($request->limit > 0) {
            $qb->setMaxResults($request->limit);
        }

        if ($request->offset > 0) {
            $qb->setFirstResult($request->offset);
        }

        return $qb;
    }

    /**
     * @param int $limit
     */
    public function getUnmatchedPayments(int $limit = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p, cur, cr, c, u')
            ->leftJoin('p.currency', 'cur')
            ->leftJoin('p.client', 'c')
            ->leftJoin('c.user', 'u')
            ->leftJoin('p.credit', 'cr')
            ->leftJoin('p.paymentCovers', 'pc')
            ->where('c.id IS NULL OR (cr.id IS NULL AND pc.id IS NULL)');

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function getByIds(array $ids): array
    {
        if (! $ids) {
            return [];
        }

        $qb = $this->createQueryBuilder('p')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $ids);

        $payments = $qb->getQuery()->getResult();
        Arrays::sortByArray($payments, $ids, 'id');

        return $payments;
    }

    /**
     * Removal (or unmatch) not possible if refund exists for payment.
     */
    public function isRemovalPossible(Payment $payment): bool
    {
        $covers = $payment->getPaymentCovers();

        foreach ($covers as $cover) {
            /** @var PaymentCover $cover */
            if ($cover->getRefund()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return int[]
     */
    public function getUsedPaymentMethods(?Client $client): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('DISTINCT p.method');

        if ($client) {
            $qb->andWhere('p.client = :client')
                ->setParameter('client', $client);
        }

        return array_column($qb->getQuery()->getArrayResult(), 'method');
    }

    /**
     * @return Payment[]
     */
    public function getMatchedByIds(array $ids): array
    {
        if (! $ids) {
            return [];
        }

        $qb = $this->getMatchedQueryBuilder($ids);

        $payments = $qb->getQuery()->getResult();

        Arrays::sortByArray($payments, $ids, 'id');

        return $payments;
    }

    public function filterMatchedIds(array $ids): array
    {
        if (! $ids) {
            return [];
        }

        $payments = $this->getMatchedQueryBuilder($ids)
            ->select('p.id')
            ->getQuery()
            ->getArrayResult();

        Arrays::sortByArray($payments, $ids, '[id]');

        return array_column($payments, 'id');
    }

    /**
     * @return Payment[]
     */
    public function findMatchedWithoutReceiptNumber(array $ids): array
    {
        if (! $ids) {
            return [];
        }

        $queryBuilder = $this->getMatchedQueryBuilder($ids)
            ->innerJoin('p.client', 'c')
            ->andWhere('p.receiptNumber IS NULL');

        return $queryBuilder->getQuery()->getResult();
    }

    private function getMatchedQueryBuilder(array $ids): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->where('p.id IN (:ids)')
            ->andWhere('p.client IS NOT NULL')
            ->setParameter('ids', $ids);
    }
}
