<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Database\UtcDateTimeType;
use AppBundle\DataProvider\Request\ClientSummaryRequest;
use AppBundle\Entity\Client;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Service;
use AppBundle\Util\Arrays;
use Doctrine\ORM\EntityManagerInterface;

class ClientSummaryDataProvider
{
    public const TAG_OVERDUE = 1;
    public const TAG_SUSPENDED = 2;
    public const TAG_OUTAGE = 3;

    public const TAGS = [
        self::TAG_OVERDUE,
        self::TAG_SUSPENDED,
        self::TAG_OUTAGE,
    ];

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getClients(ClientSummaryRequest $request): array
    {
        $data = $this->getClientsData($request);

        $ids = array_map(
            function ($row) {
                return $row['id'];
            },
            $data
        );

        $services = $request->getRelatedServices ? $this->getActiveServicesData($ids) : [];
        Arrays::addRelatedData($data, 'id', $services, 'clientId', 'activeServices');

        $invoices = $request->getRelatedInvoices ? $this->getOverdueInvoicesData($ids) : [];
        Arrays::addRelatedData($data, 'id', $invoices, 'clientId', 'overdueInvoices');

        return $data;
    }

    private function getClientsData(ClientSummaryRequest $request): array
    {
        $qb = $this->entityManager
            ->getRepository(Client::class)
            ->createQueryBuilder('client')
            ->innerJoin('client.user', 'user')
            ->innerJoin('client.organization', 'o')
            ->innerJoin('o.currency', 'cu')
            ->select(
                [
                    'client.id',
                    'user.firstName',
                    'user.lastName',
                    'client.companyName',
                    'client.balance',
                    'cu.code AS currencyCode',
                    'client.hasOverdueInvoice',
                    'client.hasSuspendedService',
                    'client.hasOutage',
                    'client.isLead',
                ]
            )
            ->andWhere('client.deletedAt IS NULL');

        if ($request->overdue !== null) {
            $qb->andWhere('client.hasOverdueInvoice = :overdue')
                ->setParameter('overdue', $request->overdue);
        }

        if ($request->suspended !== null) {
            $qb->andWhere('client.hasSuspendedService = :suspended')
                ->setParameter('suspended', $request->suspended);
        }

        if ($request->outage !== null) {
            $qb->andWhere('client.hasOutage = :outage')
                ->setParameter('outage', $request->outage);
        }

        if ($request->limit !== null) {
            $qb->setMaxResults($request->limit);
        }

        if ($request->offset !== null) {
            $qb->setFirstResult($request->offset);
        }
        $qb->addOrderBy($request->order ?: 'client.id', $request->direction ?: 'ASC');

        if (in_array($request->order, ['user.firstName', 'user.lastName'], true)) {
            $qb->addOrderBy('client.companyName', $request->direction ?: 'ASC');
        }

        if ($request->isLead !== null) {
            $qb->andWhere('client.isLead = :isLead')
                ->setParameter('isLead', $request->isLead);
        }

        return $qb->getQuery()->getResult();
    }

    private function getActiveServicesData(array $clientIds): array
    {
        if (! $clientIds) {
            return [];
        }

        $qb = $this->entityManager
            ->getRepository(Service::class)
            ->createQueryBuilder('s')
            ->innerJoin('s.client', 'c')
            ->select(
                [
                    'c.id AS clientId',
                    's.id',
                    's.name',
                ]
            )
            ->andWhere('s.deletedAt IS NULL')
            ->andWhere('s.status IN (:statuses)')
            ->setParameter('statuses', Service::ACTIVE_STATUSES)
            ->andWhere('c.id IN (:clients)')
            ->setParameter('clients', $clientIds);

        return $qb->getQuery()->getResult();
    }

    private function getOverdueInvoicesData(array $clientIds): array
    {
        if (! $clientIds) {
            return [];
        }

        $qb = $this->entityManager
            ->getRepository(Invoice::class)
            ->createQueryBuilder('i')
            ->innerJoin('i.client', 'c')
            ->innerJoin('i.currency', 'cu')
            ->select(
                [
                    'c.id AS clientId',
                    'i.id',
                    'i.invoiceStatus AS status',
                    'i.total',
                    'i.amountPaid',
                    'cu.code AS currencyCode',
                    'i.dueDate',
                ]
            )
            ->andWhere('i.invoiceStatus IN (:statuses)')
            ->setParameter('statuses', Invoice::UNPAID_STATUSES)
            ->andWhere('i.dueDate < :dueUntil')
            ->setParameter('dueUntil', new \DateTimeImmutable('today midnight'), UtcDateTimeType::NAME)
            ->andWhere('c.id IN (:clients)')
            ->setParameter('clients', $clientIds);

        return $qb->getQuery()->getResult();
    }

    public function getCountsByStatus(): array
    {
        return $this->entityManager
            ->getRepository(Client::class)
            ->createQueryBuilder('c')
            ->select(
                [
                    'SUM(CASE WHEN c.hasOutage = true THEN 1 ELSE 0 END) AS withOutage',
                    'SUM(CASE WHEN c.hasOverdueInvoice = true THEN 1 ELSE 0 END) AS withOverdueInvoice',
                    'SUM(CASE WHEN c.hasSuspendedService = true THEN 1 ELSE 0 END) AS withSuspendedService',
                    'SUM(CASE WHEN c.isLead = true THEN 1 ELSE 0 END) AS clientLeads',
                    'COUNT(c.id) AS totalCount',
                ]
            )
            ->where('c.deletedAt IS NULL')
            ->getQuery()
            ->getResult();
    }
}
