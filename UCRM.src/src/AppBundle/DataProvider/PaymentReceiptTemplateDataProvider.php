<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\Organization;
use AppBundle\Entity\Payment;
use AppBundle\Entity\PaymentReceiptTemplate;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class PaymentReceiptTemplateDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getGridModel(): QueryBuilder
    {
        return $this->entityManager->getRepository(PaymentReceiptTemplate::class)
            ->createQueryBuilder('prt')
            ->andWhere('prt.deletedAt IS NULL');
    }

    public function getAllPaymentReceiptTemplates(): array
    {
        return $this->entityManager->getRepository(PaymentReceiptTemplate::class)
            ->findBy(
                [
                    'deletedAt' => null,
                ],
                [
                    'name' => 'ASC',
                    'id' => 'ASC',
                ]
            );
    }

    public function isUsedOnOrganization(PaymentReceiptTemplate $paymentReceiptTemplate): bool
    {
        return (bool) $this->entityManager->getRepository(Organization::class)
            ->createQueryBuilder('o')
            ->select('1')
            ->where('o.paymentReceiptTemplate = :paymentReceiptTemplate')
            ->setParameter('paymentReceiptTemplate', $paymentReceiptTemplate)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function isUsedOnPayment(PaymentReceiptTemplate $paymentReceiptTemplate): bool
    {
        return (bool) $this->entityManager->getRepository(Payment::class)
            ->createQueryBuilder('p')
            ->select('1')
            ->where('p.paymentReceiptTemplate = :paymentReceiptTemplate')
            ->setParameter('paymentReceiptTemplate', $paymentReceiptTemplate)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
