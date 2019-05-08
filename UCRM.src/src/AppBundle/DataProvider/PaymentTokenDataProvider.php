<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\PaymentToken;
use Doctrine\ORM\EntityManager;

class PaymentTokenDataProvider
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getInvoiceIdsWithPendingPayments($client): array
    {
        $result = $this->entityManager->getRepository(PaymentToken::class)
            ->createQueryBuilder('pt')
            ->select('i.id')
            ->join('pt.paymentStripePending', 'pst')
            ->join('pt.invoice', 'i')
            ->andWhere('i.client = :client')
            ->setParameter('client', $client)
            ->getQuery()
            ->getScalarResult();

        return array_column($result, 'id');
    }
}
