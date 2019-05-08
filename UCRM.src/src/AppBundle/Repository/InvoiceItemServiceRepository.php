<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Repository;

use AppBundle\Database\UtcDateTimeType;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\InvoiceItemService;
use AppBundle\Entity\Service;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\QueryBuilder;

class InvoiceItemServiceRepository extends BaseRepository
{
    public function hasInvoice(Service $service): bool
    {
        $qb = $this->createExistsQueryBuilder($service);

        return (bool) $qb->getQuery()->getOneOrNullResult();
    }

    public function hasDraft(Service $service): bool
    {
        $qb = $this->createExistsQueryBuilder($service);
        $qb->join('iis.invoice', 'i');
        $qb->andWhere('i.invoiceStatus = :draft');
        $qb->setParameter('draft', Invoice::DRAFT);

        return (bool) $qb->getQuery()->getOneOrNullResult();
    }

    private function createExistsQueryBuilder(Service $service): QueryBuilder
    {
        $qb = $this->createQueryBuilder('iis');
        $qb->select('1');
        $qb->andWhere('iis.service = :service OR iis.originalService = :service');
        $qb->setParameter('service', $service);
        $qb->setMaxResults(1);

        return $qb;
    }

    /**
     * @return InvoiceItemService[]
     */
    public function getOverdueInvoiceItemsForSuspension(
        bool $defaultStopServiceDue,
        int $defaultStopServiceDueDays,
        ?Service $service = null
    ): array {
        $qb = $this->getOverdueInvoiceItemsForSuspensionQueryBuilder(
            $defaultStopServiceDue,
            $defaultStopServiceDueDays
        );

        // Manual suspension
        if ($service) {
            $qb
                ->andWhere('iis.service = :service')
                ->setParameter('service', $service);
        } else {
            $qb
                ->andWhere('s.stopReason IS NULL')
                ->andWhere('(s.suspendedFrom IS NULL OR s.suspendedFrom <= :suspendedFrom)')
                ->setParameter('suspendedFrom', new \DateTime(), UtcDateTimeType::NAME);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return InvoiceItemService[]
     */
    public function getInvoiceItemsCausingSuspensionForService(
        bool $defaultStopServiceDue,
        int $defaultStopServiceDueDays,
        Service $service
    ): array {
        $qb = $this->getOverdueInvoiceItemsForSuspensionQueryBuilder($defaultStopServiceDue, $defaultStopServiceDueDays)
            ->andWhere('iis.service = :service')
            ->setParameter('service', $service);

        return $qb->getQuery()->getResult();
    }

    /**
     * This method is used to get invoices for a service, that had suspension
     * cancelled manually, but would still cause suspension otherwise.
     *
     * @return Invoice[]
     */
    public function getInvoicesWithCancelledSuspension(
        bool $defaultStopServiceDue,
        int $defaultStopServiceDueDays,
        Service $service
    ): array {
        $qb = $this->getOverdueInvoiceItemsForSuspensionQueryBuilder($defaultStopServiceDue, $defaultStopServiceDueDays)
            ->andWhere('iis.service = :service')
            ->setParameter('service', $service)
            ->setParameter('canCauseSuspension', false);

        $items = $qb->getQuery()->getResult();
        $invoices = [];
        /** @var InvoiceItemService $item */
        foreach ($items as $item) {
            $invoices[$item->getInvoice()->getId()] = $item->getInvoice();
        }

        return $invoices;
    }

    /**
     * Used to detect if there are any invoices that would cause suspension.
     * If not, unsuspend is possible. The query is basically the same as getOverdueInvoiceItemsForSuspension.
     *
     * Ignored invoices are there because this method is used in a subscriber before database flush,
     * so the database still contains newly paid / voided or deleted invoices.
     */
    public function canUnsuspendService(
        bool $defaultStopServiceDue,
        int $defaultStopServiceDueDays,
        Service $service,
        array $ignoredInvoices
    ): bool {
        $qb = $this->getOverdueInvoiceItemsForSuspensionQueryBuilder($defaultStopServiceDue, $defaultStopServiceDueDays)
            ->andWhere('iis.invoice NOT IN (:ignoredInvoices)')
            ->andWhere('iis.service = :service')
            ->setParameter('service', $service)
            ->setParameter('ignoredInvoices', $ignoredInvoices)
            ->setMaxResults(1);

        return ! ((bool) $qb->getQuery()->getOneOrNullResult());
    }

    /**
     * @return Invoice[]
     */
    public function getOverdueInvoicesForLateFees(int $defaultLateFeeDelayDays): array
    {
        // For correct "day" truncation of i.dueDate, we have to move the i.dueDate to system timezone
        // in the query, not the other way around as usual.
        //
        // For example:
        // due date: 2018-05-17 01:00:00 (-06:00)
        // late fee delay: 0
        // current time: 2018-05-16 23:59:00 (-06:00)
        //
        // WRONG:
        // In database, due date: 2018-05-17 07:00:00 (UTC)
        // We move the current time to UTC as usual: 2018-05-17 05:59:00 (UTC)
        // now we truncate to day and compare:
        // 2018-05-17 00:00:00 <= 2018-05-17 05:59:00
        // = TRUE, late fee IS created even though it should NOT
        //
        // CORRECT:
        // We move the due date to system timezone: 2018-05-17 01:00:00
        // We leave the current time in system timezone: 2018-05-16 23:59:00
        // now we truncate to day and compare:
        // 2018-05-17 00:00:00 <= 2018-05-16 23:59:00
        // = FALSE, late fee IS NOT created, as it should
        //
        // CORRECT:
        // current time: 2018-05-17 00:30:00 (-06:00)
        // We move the due date to system timezone: 2018-05-17 01:00:00
        // We leave the current time in system timezone: 2018-05-17 00:30:00
        // now we truncate to day and compare:
        // 2018-05-17 00:00:00 <= 2018-05-17 00:30:00
        // = TRUE, late fee IS created, as it should

        $now = new \DateTime();

        /** @var InvoiceItemService[] $items */
        $items = $this->createQueryBuilder('iis')
            ->select('iis, i, s, c')
            ->join('iis.invoice', 'i')
            ->join('iis.service', 's')
            ->join('s.client', 'c')
            ->andWhere('i.invoiceStatus IN(:unpaid)')
            ->andWhere('i.lateFeeCreated = false')
            ->andWhere(
                'date_trunc(
                    \'day\',
                    DATE_ADD(
                        DATE_ADD(i.dueDate, :tzOffset, \'second\'),
                        COALESCE(c.lateFeeDelayDays, :defaultLateFeeDelayDays),
                        \'day\'
                    )
                ) <= :now'
            )
            ->andWhere('c.deletedAt IS NULL')
            ->andWhere('c.isLead = false')
            ->andWhere('s.deletedAt IS NULL')
            ->setParameter('unpaid', Invoice::UNPAID_STATUSES)
            ->setParameter('defaultLateFeeDelayDays', $defaultLateFeeDelayDays)
            ->setParameter('now', $now, Type::DATETIME)// intentionally NOT `UtcDateTimeType::NAME`
            ->setParameter('tzOffset', $now->getTimezone()->getOffset($now))
            ->getQuery()
            ->getResult();

        $invoices = [];
        foreach ($items as $item) {
            $invoice = $item->getInvoice();
            if (array_key_exists($invoice->getId(), $invoices)) {
                continue;
            }

            $invoices[$invoice->getId()] = $invoice;
        }

        return $invoices;
    }

    private function getOverdueInvoiceItemsForSuspensionQueryBuilder(
        bool $defaultStopServiceDue,
        int $defaultStopServiceDueDays
    ): QueryBuilder {
        // see InvoiceItemServiceRepository::getOverdueInvoicesForLateFees() for explanation of the timezone

        $now = new \DateTime();

        return $this->createQueryBuilder('iis')
            ->select('iis')
            ->join('iis.invoice', 'i')
            ->join('iis.service', 's')
            ->join('s.client', 'c')
            ->andWhere('i.invoiceStatus IN(:statuses)')
            ->andWhere(
                'date_trunc(
                    \'day\',
                    DATE_ADD(
                        DATE_ADD(i.dueDate, :tzOffset, \'second\'),
                        COALESCE(c.stopServiceDueDays, :defaultStopServiceDueDays),
                        \'day\'
                    )
                ) <= :now'
            )
            ->andWhere('c.deletedAt IS NULL')
            ->andWhere('c.isLead = false')
            ->andWhere('s.deletedAt IS NULL')
            ->andWhere('COALESCE(c.stopServiceDue, :defaultStopServiceDue) = TRUE')
            ->andWhere('i.canCauseSuspension = :canCauseSuspension')
            ->setParameter('statuses', Invoice::UNPAID_STATUSES)
            ->setParameter('defaultStopServiceDue', $defaultStopServiceDue)
            ->setParameter('defaultStopServiceDueDays', $defaultStopServiceDueDays)
            ->setParameter('now', $now, Type::DATETIME)// intentionally NOT `UtcDateTimeType::NAME`
            ->setParameter('tzOffset', $now->getTimezone()->getOffset($now))
            ->setParameter('canCauseSuspension', true);
    }
}
