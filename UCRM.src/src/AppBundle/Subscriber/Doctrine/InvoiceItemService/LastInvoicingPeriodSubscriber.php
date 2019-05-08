<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Doctrine\InvoiceItemService;

use AppBundle\Entity\Financial\InvoiceItemService;
use AppBundle\Entity\Service;
use AppBundle\Util\Invoicing;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use EntitySubscribersBundle\Event\EntityEventSubscriber;

class LastInvoicingPeriodSubscriber implements EntityEventSubscriber
{
    public function subscribesToEntity(LoadClassMetadataEventArgs $event): bool
    {
        return InvoiceItemService::class === $event->getClassMetadata()->getName();
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function prePersist(InvoiceItemService $invoiceItemService, LifecycleEventArgs $eventArgs): void
    {
        $this->preUpdate($invoiceItemService, $eventArgs);
    }

    public function preUpdate(InvoiceItemService $invoiceItemService, LifecycleEventArgs $eventArgs): void
    {
        $service = $invoiceItemService->getOriginalService() ?: $invoiceItemService->getService();

        if (
            ! $service
            || ($service->isDeleted() && $service->getStatus() !== Service::STATUS_OBSOLETE)
            || ! $service->getClient()
            || $service->getClient()->isDeleted()
            || ! $invoiceItemService->getInvoicedTo()
        ) {
            return;
        }

        if (
            ! $service->getInvoicingLastPeriodEnd()
            || $service->getInvoicingLastPeriodEnd() < $invoiceItemService->getInvoicedTo()
        ) {
            $oldInvoicingLastPeriodEnd = $service->getInvoicingLastPeriodEnd();
            $oldNextInvoicingDay = $service->getNextInvoicingDay();

            $service->setInvoicingLastPeriodEnd(clone $invoiceItemService->getInvoicedTo());
            $service->setNextInvoicingDay(Invoicing::getNextInvoicingDay($service));

            $entityManager = $eventArgs->getEntityManager();

            // Dark Doctrine magic here.
            // Calling recomputeSingleEntityChangeSet() does not work here, not even with scheduleForUpdate().
            $entityManager->getUnitOfWork()->scheduleExtraUpdate(
                $service,
                [
                    'invoicingLastPeriodEnd' => [
                        $oldInvoicingLastPeriodEnd,
                        $service->getInvoicingLastPeriodEnd(),
                    ],
                    'nextInvoicingDay' => [
                        $oldNextInvoicingDay,
                        $service->getNextInvoicingDay(),
                    ],
                ]
            );
        }
    }
}
