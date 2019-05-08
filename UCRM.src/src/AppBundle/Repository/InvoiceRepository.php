<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Repository;

use ApiBundle\Request\InvoiceCollectionRequest;
use AppBundle\Database\UtcDateTimeType;
use AppBundle\Entity\Client;
use AppBundle\Entity\ClientContact;
use AppBundle\Entity\Currency;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\InvoiceItemFee;
use AppBundle\Entity\Financial\InvoiceItemService;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Service;
use AppBundle\Util\Arrays;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;

class InvoiceRepository extends BaseRepository
{
    public function getClientUnpaidInvoicesQueryBuilder(Client $client): QueryBuilder
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.client = :client')
            ->andWhere('i.invoiceStatus IN(:statuses)')
            ->addOrderBy('i.dueDate')
            ->addOrderBy('i.id')
            ->setParameter('statuses', Invoice::UNPAID_STATUSES)
            ->setParameter('client', $client);
    }

    public function getQueryBuilder(InvoiceCollectionRequest $request): QueryBuilder
    {
        $qb = $this->createQueryBuilder('invoice');

        if ($request->organizationId) {
            $qb->andWhere('invoice.organization = :organization')
                ->setParameter('organization', $request->organizationId);
        }

        if ($request->clientId) {
            $qb->andWhere('invoice.client = :client')
                ->setParameter('client', $request->clientId);
        }

        if ($request->startDate) {
            $qb->andWhere('invoice.createdDate >= :startDate')
                ->setParameter('startDate', $request->startDate, UtcDateTimeType::NAME);
        }

        if ($request->endDate) {
            $qb->andWhere('invoice.createdDate <= :endDate')
                ->setParameter('endDate', $request->endDate, UtcDateTimeType::NAME);
        }

        if ($request->currency) {
            $qb->andWhere('invoice.currency = :currency')
                ->setParameter('currency', $request->currency);
        }

        if ($request->statuses) {
            $qb->andWhere('invoice.invoiceStatus IN (:statuses)');
            $qb->setParameter('statuses', $request->statuses);
        }

        if ($request->number !== null) {
            $qb->andWhere('invoice.invoiceNumber = :invoiceNumber');
            $qb->setParameter('invoiceNumber', $request->number);
        }

        if ($request->limit !== null) {
            $qb->setMaxResults($request->limit);
        }

        if ($request->offset !== null) {
            $qb->setFirstResult($request->offset);
        }

        if ($request->proforma !== null) {
            $qb->andWhere('invoice.isProforma = :isProforma');
            $qb->setParameter('isProforma', $request->proforma);
        }

        if ($request->overdue !== null) {
            $now = new \DateTime('today midnight');
            if ($request->overdue) {
                $qb
                    ->andWhere('invoice.invoiceStatus IN (:unpaidStatuses)')
                    ->setParameter('unpaidStatuses', Invoice::UNPAID_STATUSES)
                    ->andWhere('invoice.dueDate <= :now')
                    ->setParameter('now', $now);
            } else {
                $qb->andWhere('invoice.dueDate > :now OR invoice.invoiceStatus NOT IN (:unpaidStatuses)')
                    ->setParameter('now', $now)
                    ->setParameter('unpaidStatuses', Invoice::UNPAID_STATUSES);
                $qb->andWhere('invoice.invoiceStatus != :invoiceStatus')
                    ->setParameter('invoiceStatus', Invoice::DRAFT);
            }
        }

        if ($request->getCustomAttributeKey()) {
            $qb->join('invoice.attributes', 'ia');
            $qb->join('ia.attribute', 'a');
            $qb->andWhere('a.key = :key');
            $qb->setParameter('key', $request->getCustomAttributeKey());
            $qb->andWhere('ia.value = :value');
            $qb->setParameter('value', $request->getCustomAttributeValue());
        }

        return $qb;
    }

    /**
     * @return Invoice[]
     */
    public function getClientUnpaidInvoices(Client $client): array
    {
        return $this->getClientUnpaidInvoicesQueryBuilder($client)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Invoice[]
     */
    public function getClientUnpaidInvoicesWithCurrency(Client $client, Currency $currency): array
    {
        return $this->getClientUnpaidInvoicesQueryBuilder($client)
            ->andWhere('i.currency = :currency')
            ->setParameter('currency', $currency)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Invoice[]
     */
    public function getOverdueInvoicesForNotifications(int $limit): array
    {
        $qb = $this->createQueryBuilder('i')
            ->select('i')
            ->where('i.dueDate < :to')
            ->andWhere('i.overdueNotificationSent = false')
            ->andWhere('i.invoiceStatus IN (:statuses)')
            ->setMaxResults($limit)
            ->setParameter('to', new \DateTime('today midnight'), UtcDateTimeType::NAME)
            ->setParameter('statuses', Invoice::UNPAID_STATUSES);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Invoice[]
     */
    public function getNearDueInvoicesForNotifications(int $limit, int $daysBeforeDue): array
    {
        $qb = $this->createQueryBuilder('i')
            ->select('i')
            ->where('i.dueDate < :before')
            ->andWhere('i.dueDate >= :today')
            ->andWhere('i.nearDueNotificationSent = false')
            ->andWhere('i.overdueNotificationSent = false')
            ->andWhere('i.invoiceStatus IN (:statuses)')
            ->setMaxResults($limit)
            ->setParameter('before', new \DateTime(sprintf('+%d days midnight', $daysBeforeDue)), UtcDateTimeType::NAME)
            ->setParameter('today', new \DateTime('today midnight'), UtcDateTimeType::NAME)
            ->setParameter('statuses', Invoice::UNPAID_STATUSES);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Invoice[]
     */
    public function getLastOverdueInvoices(int $limit): array
    {
        return $this
            ->createQueryBuilder('i')
            ->select('i')
            ->andWhere('i.dueDate < :to')
            ->andWhere('i.invoiceStatus IN (:statuses)')
            ->andWhere('i.uncollectible = false')
            ->orderBy('i.id', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('to', new \DateTime('today midnight'), UtcDateTimeType::NAME)
            ->setParameter('statuses', Invoice::UNPAID_STATUSES)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Invoice[]
     */
    public function getInvoicesToSend(bool $includeZeroBalance): array
    {
        $qb = $this->createQueryBuilder('i')
            ->addSelect('c, u, ipt')
            ->join('i.client', 'c')
            ->join('c.user', 'u')
            ->leftJoin('i.paymentToken', 'ipt')
            ->where('i.invoiceStatus IN (:status)')
            ->andWhere('i.emailSentDate is NULL')
            ->andWhere('c.deletedAt IS NULL')
            ->andWhere('c.isLead = false')
            ->addGroupBy('i.id, c.id, u.id, ipt.id')
            ->addOrderBy('i.createdDate')
            ->setParameter('status', Invoice::VALID_STATUSES);

        if (! $includeZeroBalance) {
            $qb->andWhere('round(i.total - i.amountPaid, 2) > 0');
        }

        $invoices = $qb->getQuery()->getResult();

        $this->loadContacts($invoices);

        return $invoices;
    }

    public function existInvoicesToSend(bool $includeZeroBalance): bool
    {
        $qb = $this->createQueryBuilder('i')
            ->select('1')
            ->join('i.client', 'c')
            ->where('i.invoiceStatus IN (:status)')
            ->andWhere('i.emailSentDate is NULL')
            ->andWhere('c.deletedAt IS NULL')
            ->andWhere('c.isLead = false')
            ->setParameter('status', Invoice::VALID_STATUSES);

        if (! $includeZeroBalance) {
            $qb->andWhere('round(i.total - i.amountPaid, 2) > 0');
        }

        return (bool) $qb->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param Invoice[] $invoices
     */
    private function loadContacts(array $invoices): void
    {
        $ids = array_map(
            function (Invoice $invoice) {
                return $invoice->getClient()->getId();
            },
            $invoices
        );
        $this->_em->getRepository(Client::class)->loadRelatedEntities('contacts', $ids);

        $contactIds = [];
        foreach ($invoices as $invoice) {
            foreach ($invoice->getClient()->getContacts() as $contact) {
                $contactIds[] = $contact->getId();
            }
        }
        $this->_em->getRepository(ClientContact::class)->loadRelatedEntities('types', $contactIds);
    }

    public function getCount(): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getInvoicesTotalSum(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        Organization $organization
    ): ?float {
        $qb = $this->createInvoiceRevenueReportQueryBuilder($from, $to, $organization)
            ->select('SUM(i.total) as total')
            ->setParameter(
                'statuses',
                [
                    Invoice::PAID,
                    Invoice::UNPAID,
                    Invoice::PARTIAL,
                ]
            );

        try {
            return (float) $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    public function getInvoicesUnpaidSum(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        Organization $organization
    ): ?float {
        $qb = $this->createInvoiceRevenueReportQueryBuilder($from, $to, $organization)
            ->select('SUM(i.total - i.amountPaid) as total')
            ->setParameter(
                'statuses',
                [
                    Invoice::UNPAID,
                    Invoice::PARTIAL,
                ]
            );

        try {
            return (float) $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    public function getInvoicesOverdueSum(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        Organization $organization
    ): ?float {
        $qb = $this->createInvoiceRevenueReportQueryBuilder($from, $to, $organization)
            ->select('SUM(i.total - i.amountPaid) as total')
            ->andWhere('i.dueDate < :now')
            ->setParameter('now', new \DateTime())
            ->setParameter(
                'statuses',
                [
                    Invoice::UNPAID,
                    Invoice::PARTIAL,
                ]
            );

        try {
            return (float) $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    public function getInvoicesPaidSum(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        Organization $organization
    ): ?float {
        $qb = $this->createInvoiceRevenueReportQueryBuilder($from, $to, $organization)
            ->select('SUM(i.amountPaid) as total')
            ->setParameter(
                'statuses',
                [
                    Invoice::PAID,
                    Invoice::PARTIAL,
                ]
            );

        try {
            return (float) $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    public function getInvoicesTotalTax(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        Organization $organization
    ): ?float {
        $qb = $this->createInvoiceRevenueReportQueryBuilder($from, $to, $organization)
            ->select('SUM(i.totalTaxAmount) as total')
            ->setParameter(
                'statuses',
                [
                    Invoice::PAID,
                    Invoice::UNPAID,
                    Invoice::PARTIAL,
                ]
            );

        try {
            return (float) $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    public function getInvoicesTotalTaxed(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        Organization $organization
    ): ?float {
        $qb = $this->createInvoiceRevenueReportQueryBuilder($from, $to, $organization)
            ->select('SUM(i.total) as total')
            ->setParameter(
                'statuses',
                [
                    Invoice::PAID,
                    Invoice::UNPAID,
                    Invoice::PARTIAL,
                ]
            );

        try {
            return (float) $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    public function createInvoiceRevenueReportQueryBuilder(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        Organization $organization
    ): QueryBuilder {
        return $this->createQueryBuilder('i')
            ->join('i.client', 'c')
            ->join('i.currency', 'cc')
            ->where('i.createdDate >= :from')
            ->andWhere('i.createdDate < :to')
            ->andWhere('i.organization = :organization')
            ->andWhere('i.invoiceStatus in (:statuses)')
            ->setParameter('from', $from->format('Y-m-d'), UtcDateTimeType::NAME)
            ->setParameter('to', $to->modify('+1 day')->format('Y-m-d'), UtcDateTimeType::NAME)
            ->setParameter('organization', $organization);
    }

    /**
     * @return Invoice[]
     */
    public function getExportableByIds(array $ids): array
    {
        if (count($ids) === 0) {
            return [];
        }

        $statuses = [
            Invoice::VOID,
            Invoice::DRAFT,
        ];

        $qb = $this->createQueryBuilder('i')
            ->addSelect('cc')
            ->addSelect('o')
            ->addSelect('cl')
            ->addSelect('u')
            ->leftJoin('i.currency', 'cc')
            ->join('i.organization', 'o')
            ->join('i.client', 'cl')
            ->join('cl.user', 'u')
            ->where('i.id IN (:ids)')
            ->andWhere('i.invoiceStatus NOT IN (:statuses)')
            ->setParameter('ids', $ids)
            ->setParameter('statuses', $statuses);

        $invoices = $qb->getQuery()->getResult();

        $this->loadRelatedEntities('invoiceItems', $ids);

        Arrays::sortByArray($invoices, $ids, 'id');

        return $invoices;
    }

    /**
     * @return Invoice[]
     */
    public function getServiceInvoices(Service $service): array
    {
        $invoices = $this->createQueryBuilder('i')
            ->select('i')
            ->join('i.invoiceItems', 'ii')
            ->leftJoin(InvoiceItemService::class, 'iis', 'WITH', 'iis.id = ii.id')
            ->leftJoin(InvoiceItemFee::class, 'iif', 'WITH', 'iif.id = ii.id')
            ->leftJoin('iif.fee', 'f')
            ->andWhere('iis.service = :service OR f.service = :service')
            ->setParameter('service', $service)
            ->getQuery()
            ->getResult();

        $invoiceIds = array_map(
            function (Invoice $invoice) {
                return $invoice->getId();
            },
            $invoices
        );

        $this->loadRelatedEntities('invoiceItems', $invoiceIds);

        return $invoices;
    }

    public function getFirstOverdueInvoice(Client $client, float $minimumUnpaidAmount): ?Invoice
    {
        $qb = $this->getClientUnpaidInvoicesQueryBuilder($client);

        $qb
            ->andWhere('i.dueDate < :today')
            ->setParameter('today', new \DateTime('today midnight'), UtcDateTimeType::NAME)
            ->andWhere('(i.total - i.amountPaid) >= :minAmount')
            ->setParameter('minAmount', $minimumUnpaidAmount)
            ->andWhere('i.invoiceStatus IN (:unpaidStatuses)')
            ->setParameter('unpaidStatuses', Invoice::UNPAID_STATUSES)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
