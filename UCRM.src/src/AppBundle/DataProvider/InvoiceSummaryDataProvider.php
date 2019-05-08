<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Database\UtcDateTimeType;
use AppBundle\Entity\Financial\Invoice;
use Doctrine\ORM\EntityManagerInterface;

class InvoiceSummaryDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getInvoices(
        ?array $statuses,
        bool $onlyOverdue,
        ?int $limit,
        ?int $offset,
        ?string $order = null,
        ?string $direction = null
    ): array {
        $dueUntil = $onlyOverdue ? new \DateTimeImmutable('today midnight') : null;

        $qb = $this->entityManager
            ->getRepository(Invoice::class)
            ->createQueryBuilder('i')
            ->innerJoin('i.currency', 'c')
            ->select(
                [
                    'i.id',
                    'i.invoiceNumber AS number',
                    'i.invoiceStatus AS status',
                    'i.clientFirstName',
                    'i.clientLastName',
                    'i.clientCompanyName',
                    'i.total',
                    'i.amountPaid',
                    'c.code AS currencyCode',
                    'i.createdDate',
                    'i.dueDate',
                ]
            );

        if ($statuses) {
            $qb
                ->andWhere('i.invoiceStatus IN (:statuses)')
                ->setParameter('statuses', $statuses);
        }

        if ($dueUntil) {
            $qb
                ->andWhere('i.invoiceStatus IN (:unpaid)')
                ->setParameter('statuses', Invoice::UNPAID_STATUSES)
                ->andWhere('i.dueDate < :dueUntil')
                ->setParameter('dueUntil', $dueUntil, UtcDateTimeType::NAME);
        }

        if ($limit > 0) {
            $qb->setMaxResults($limit);
        }

        if ($offset > 0) {
            $qb->setFirstResult($offset);
        }

        $qb->addOrderBy('i.' . ($order ?: 'createdDate'), $direction ?: 'DESC');
        $qb->addOrderBy('i.id', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function getCountsByStatus(): array
    {
        $qb = $this->entityManager
            ->getRepository(Invoice::class)
            ->createQueryBuilder('i')
            ->select(
                [
                    'i.invoiceStatus AS status',
                    'COUNT(i.id) AS invoicesCount',
                ]
            )
            ->groupBy(
                'i.invoiceStatus'
            )
        ;

        return $qb->getQuery()->getResult();
    }
}
