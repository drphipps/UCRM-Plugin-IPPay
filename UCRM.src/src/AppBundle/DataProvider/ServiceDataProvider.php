<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use ApiBundle\Request\ServiceCollectionRequest;
use AppBundle\Entity\Client;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceStopReason;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class ServiceDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getCollection(ServiceCollectionRequest $request): array
    {
        $qb = $this->getServiceQueryBuilder($request)
            ->orderBy('service.id', 'ASC');

        $services = $qb->getQuery()->getResult();

        $this->loadServiceRelatedEntities($services);

        return $services;
    }

    /**
     * Checks if the given service has an older obsolete service. This method exists because having
     * inverse-side of the relation on the entity can cause performance issues as it can't be lazy.
     */
    public function hasSupersededService(Service $service): bool
    {
        $repository = $this->entityManager->getRepository(Service::class);

        return (bool) $repository->findOneBy(
            [
                'supersededByService' => $service,
            ]
        );
    }

    public function getServicesForLinkedSubscriptionsQueryBuilder(Client $client): QueryBuilder
    {
        return $this->entityManager->getRepository(Service::class)
            ->createQueryBuilder('s')
            ->where('s.deletedAt IS NULL')
            ->andWhere('s.status IN (:statuses)')
            ->andWhere('s.client = :client')
            ->setParameter('statuses', Service::ACTIVE_STATUSES)
            ->setParameter('client', $client->getId())
            ->orderBy('s.id');
    }

    public function getServicesForLinkedSubscriptions(Client $client): array
    {
        $services = $this->getServicesForLinkedSubscriptionsQueryBuilder($client)
            ->getQuery()
            ->getResult();

        $this->loadServiceRelatedEntities($services);

        return $services;
    }

    private function loadServiceRelatedEntities(array $services): void
    {
        $ids = array_map(
            function (Service $service) {
                return $service->getId();
            },
            $services
        );
        $repository = $this->entityManager->getRepository(Service::class);

        $repository->loadRelatedEntities('serviceDevices', $ids);
        $repository->loadRelatedEntities('tariff', $ids);
        $repository->loadRelatedEntities('tariffPeriod', $ids);
        $repository->loadRelatedEntities(['serviceDevices', 'serviceIps'], $ids);
        $repository->loadRelatedEntities('serviceSurcharges', $ids);
        $repository->loadRelatedEntities('client', $ids);
        $repository->loadRelatedEntities(['client', 'organization'], $ids);
    }

    /**
     * @return Service[]
     */
    public function getServicesPreparedForActivation(): array
    {
        return $this->entityManager->getRepository(Service::class)
            ->createQueryBuilder('s')
            ->where('s.activeFrom <= :now')
            ->andWhere('s.stopReason = :preparedBlocked')
            ->setParameter('now', (new \DateTime())->format(\DateTime::ISO8601))
            ->setParameter('preparedBlocked', ServiceStopReason::STOP_REASON_PREPARED_ID)
            ->getQuery()
            ->getResult();
    }

    public function getFirstOverdueInvoice(Service $service, $minimumUnpaidAmount = 0.0): ?Invoice
    {
        $this->entityManager->getRepository(Service::class)->loadRelatedEntities('suspendedByInvoices', [$service->getId()]);
        foreach ($service->getSuspendedByInvoices() as $invoice) {
            // already sorted by due date, getting oldest first
            if ($invoice->isCanCauseSuspension()
                && $invoice->getAmountToPay() >= $minimumUnpaidAmount
                && in_array(
                    $invoice->getInvoiceStatus(),
                    Invoice::UNPAID_STATUSES,
                    true
                )
            ) {
                return $invoice;
            }
        }

        return $this->entityManager->getRepository(Invoice::class)->getFirstOverdueInvoice(
            $service->getClient(),
            $minimumUnpaidAmount
        );
    }

    /**
     * @throws \Doctrine\ORM\Query\QueryException
     */
    private function getServiceQueryBuilder(ServiceCollectionRequest $request): QueryBuilder
    {
        $qb = $this->entityManager->getRepository(Service::class)->createQueryBuilder('service');

        if ($request->clientId) {
            $qb->andWhere('service.client = :client')
                ->setParameter('client', $request->clientId);
        }

        if ($request->organizationId) {
            $qb->join('service.client', 'client')
                ->andWhere('client.organization = :organization')
                ->setParameter('organization', $request->organizationId);
        }

        if ($request->statuses) {
            $qb->andWhere('service.status IN (:statuses)')
                ->setParameter('statuses', $request->statuses);
        }

        if ($request->limit !== null) {
            $qb->setMaxResults($request->limit);
        }

        if ($request->offset !== null) {
            $qb->setFirstResult($request->offset);
        }

        $qb->andWhere('service.deletedAt IS NULL');

        return $qb;
    }
}
