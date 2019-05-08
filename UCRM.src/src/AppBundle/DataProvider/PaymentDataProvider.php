<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use ApiBundle\Request\PaymentCollectionRequest;
use AppBundle\Entity\Payment;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class PaymentDataProvider
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
     * @return Payment[]
     *
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function getCollection(PaymentCollectionRequest $request): array
    {
        $request->order = $request->order ?? 'createdDate';

        return $this->entityManager->getRepository(Payment::class)->getQueryBuilder($request)->getQuery()->getResult();
    }

    public function getGridModel(bool $onlyUnmatched): QueryBuilder
    {
        $qb = $this->entityManager->getRepository(Payment::class)->createQueryBuilder('p');
        $qb->addSelect('c, u, cur, cr, o.name as o_name, o.id as o_id, au')
            ->addSelect('client_full_name(c, u) AS c_fullname')
            ->addSelect('au.fullName AS au_user')
            ->addSelect('p.method AS p_method')
            ->addSelect('p.amount AS p_amount')
            ->leftJoin('p.currency', 'cur')
            ->leftJoin('p.paymentCovers', 'pc')
            ->leftJoin('p.client', 'c')
            ->leftJoin('c.user', 'u')
            ->leftJoin('c.organization', 'o')
            ->leftJoin('p.credit', 'cr')
            ->leftJoin('p.user', 'au');

        if ($onlyUnmatched) {
            $qb->andWhere('c.id IS NULL OR (cr.id IS NULL AND pc.id IS NULL)');
        }

        $qb->groupBy('p.id, c.id, u.id, o.id, cur.id, cr.id, au.id');

        return $qb;
    }
}
