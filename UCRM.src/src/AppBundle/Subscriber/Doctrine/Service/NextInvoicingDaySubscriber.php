<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Doctrine\Service;

use AppBundle\Entity\Service;
use AppBundle\Util\Invoicing;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use EntitySubscribersBundle\Event\EntityEventSubscriber;

class NextInvoicingDaySubscriber implements EntityEventSubscriber
{
    public function subscribesToEntity(LoadClassMetadataEventArgs $event): bool
    {
        return Service::class === $event->getClassMetadata()->getName();
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function prePersist(Service $service): void
    {
        $this->preUpdate($service);
    }

    public function preUpdate(Service $service): void
    {
        if (
            ($service->isDeleted() && $service->getStatus() !== Service::STATUS_OBSOLETE)
            || ! $service->getClient()
            || $service->getClient()->isDeleted()
        ) {
            return;
        }

        $service->setNextInvoicingDay(Invoicing::getNextInvoicingDay($service));
    }
}
