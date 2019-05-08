<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service;

use AppBundle\Component\Sync\SynchronizationManager;
use AppBundle\Entity\Service;
use AppBundle\Event\Service\ServiceEditEvent;
use AppBundle\Event\Service\ServiceSuspendEvent;
use Doctrine\ORM\EntityManager;
use TransactionEventsBundle\TransactionDispatcher;

class ServiceStatusUpdater
{
    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    /**
     * @var SynchronizationManager
     */
    private $synchronizationManager;

    public function __construct(
        TransactionDispatcher $transactionDispatcher,
        SynchronizationManager $synchronizationManager
    ) {
        $this->transactionDispatcher = $transactionDispatcher;
        $this->synchronizationManager = $synchronizationManager;
    }

    public function updateServices()
    {
        $this->transactionDispatcher->transactional(
            function (EntityManager $em) {
                $today = new \DateTime('midnight');

                $changedStatus = 0;
                $changedStatus += $em->createQueryBuilder()
                    ->update(Service::class, 's')
                    ->set('s.status', ':status')
                    ->andWhere('s.deletedAt IS NULL')
                    ->andWhere('s.activeFrom > :today')
                    ->andWhere('s.status != :status OR s.status IS NULL')
                    ->andWhere('s.status NOT IN (:internal)')
                    ->andWhere('s.stopReason IS NULL')
                    ->setParameter('today', $today->format('Y-m-d'))
                    ->setParameter('status', Service::STATUS_PREPARED)
                    ->setParameter('internal', Service::INTERNAL_STATUSES)
                    ->getQuery()
                    ->execute();

                $changedStatus += $em->createQueryBuilder()
                    ->update(Service::class, 's')
                    ->set('s.status', ':status')
                    ->andWhere('s.deletedAt IS NULL')
                    ->andWhere('s.activeFrom > :today')
                    ->andWhere('s.status != :status OR s.status IS NULL')
                    ->andWhere('s.status NOT IN (:internal)')
                    ->andWhere('s.stopReason IS NOT NULL')
                    ->setParameter('today', $today->format('Y-m-d'))
                    ->setParameter('status', Service::STATUS_PREPARED_BLOCKED)
                    ->setParameter('internal', Service::INTERNAL_STATUSES)
                    ->getQuery()
                    ->execute();

                $changedStatus += $em->createQueryBuilder()
                    ->update(Service::class, 's')
                    ->set('s.status', ':status')
                    ->andWhere('s.deletedAt IS NULL')
                    ->andWhere('s.activeFrom <= :today')
                    ->andWhere('s.activeTo >= :today OR s.activeTo IS NULL')
                    ->andWhere('s.stopReason IS NULL')
                    ->andWhere('s.status != :status OR s.status IS NULL')
                    ->andWhere('s.status NOT IN (:internal)')
                    ->setParameter('today', $today->format('Y-m-d'))
                    ->setParameter('status', Service::STATUS_ACTIVE)
                    ->setParameter('internal', Service::INTERNAL_STATUSES)
                    ->getQuery()
                    ->execute();

                $changedStatus += $em->createQueryBuilder()
                    ->update(Service::class, 's')
                    ->set('s.status', ':status')
                    ->andWhere('s.deletedAt IS NULL')
                    ->andWhere('s.activeFrom <= :today')
                    ->andWhere('s.activeTo >= :today OR s.activeTo IS NULL')
                    ->andWhere('s.stopReason IS NOT NULL')
                    ->andWhere('s.status != :status OR s.status IS NULL')
                    ->andWhere('s.status NOT IN (:internal)')
                    ->setParameter('today', $today->format('Y-m-d'))
                    ->setParameter('status', Service::STATUS_SUSPENDED)
                    ->setParameter('internal', Service::INTERNAL_STATUSES)
                    ->getQuery()
                    ->execute();

                // Services can't be ended by bulk query because of early termination fees.
                $services = $em->getRepository(Service::class)
                    ->createQueryBuilder('s')
                    ->andWhere('s.deletedAt IS NULL')
                    ->andWhere('s.activeTo < :today')
                    ->andWhere('s.status != :status OR s.status IS NULL')
                    ->andWhere('s.status NOT IN (:internal)')
                    ->andWhere('s.supersededByService IS NULL')
                    ->setParameter('today', $today->format('Y-m-d'))
                    ->setParameter('status', Service::STATUS_ENDED)
                    ->setParameter('internal', Service::INTERNAL_STATUSES)
                    ->getQuery()
                    ->getResult();

                /** @var Service $service */
                foreach ($services as $service) {
                    $serviceBeforeUpdate = clone $service;
                    $statusBefore = $service->getStatus();

                    $service->calculateStatus();

                    yield new ServiceEditEvent($service, $serviceBeforeUpdate);
                    if ($service->getStatus() === Service::STATUS_SUSPENDED && $serviceBeforeUpdate->getStatus() !== Service::STATUS_SUSPENDED) {
                        yield new ServiceSuspendEvent($service, $serviceBeforeUpdate);
                    }

                    if ($statusBefore !== $service->getStatus()) {
                        ++$changedStatus;
                    }
                }

                if ($changedStatus > 0) {
                    $this->synchronizationManager->unsynchronizeSuspend();
                }
            }
        );
    }
}
