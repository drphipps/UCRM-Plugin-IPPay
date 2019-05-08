<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Client;

use AppBundle\Entity\Client;
use AppBundle\Entity\Service;
use AppBundle\Event\Service\ServiceArchiveEvent;
use AppBundle\Event\Service\ServiceEditEvent;
use AppBundle\Event\ServiceDevice\ServiceDeviceAddEvent;
use AppBundle\Event\ServiceDevice\ServiceDeviceDeleteEvent;
use AppBundle\Event\ServiceDevice\ServiceDeviceEditEvent;
use AppBundle\Service\ServiceOutageUpdater;
use Ds\Set;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class UpdateClientOutageBadgeSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var ServiceOutageUpdater
     */
    private $serviceOutageUpdater;

    /**
     * @var Set|Client[]
     */
    private $clientsToBeUpdated;

    public function __construct(ServiceOutageUpdater $serviceOutageUpdater)
    {
        $this->serviceOutageUpdater = $serviceOutageUpdater;
        $this->clientsToBeUpdated = new Set();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ServiceDeviceAddEvent::class => 'handleServiceDeviceAddEvent',
            ServiceDeviceEditEvent::class => 'handleServiceDeviceEditEvent',
            ServiceDeviceDeleteEvent::class => 'handleServiceDeviceDeleteEvent',
            ServiceEditEvent::class => 'handleServiceEditEvent',
            ServiceArchiveEvent::class => 'handleServiceArchiveEvent',
        ];
    }

    public function handleServiceDeviceAddEvent(ServiceDeviceAddEvent $event): void
    {
        $service = $event->getServiceDevice()->getService();

        if ($service) {
            $this->updateClientBadge($service->getClient());
        }
    }

    public function handleServiceDeviceEditEvent(ServiceDeviceEditEvent $event): void
    {
        $service = $event->getServiceDevice()->getService();

        if ($service) {
            $this->updateClientBadge($service->getClient());
        }
    }

    public function handleServiceDeviceDeleteEvent(ServiceDeviceDeleteEvent $event): void
    {
        $service = $event->getServiceDevice()->getService();

        if ($service) {
            $this->updateClientBadge($service->getClient());
        }
    }

    public function handleServiceEditEvent(ServiceEditEvent $event): void
    {
        $service = $event->getService();
        $serviceBeforeUpdate = $event->getServiceBeforeUpdate();

        $service->calculateStatus();

        if (
            (
                $serviceBeforeUpdate->getStatus() !== Service::STATUS_ENDED
                && $service->getStatus() === Service::STATUS_ENDED
            )
            || (
                $serviceBeforeUpdate->getStatus() !== Service::STATUS_OBSOLETE
                && $service->getStatus() === Service::STATUS_OBSOLETE
            )
        ) {
            $this->updateClientBadge($service->getClient());
        }
    }

    public function handleServiceArchiveEvent(ServiceArchiveEvent $event): void
    {
        $this->updateClientBadge($event->getService()->getClient());
    }

    private function updateClientBadge(Client $client): void
    {
        $this->clientsToBeUpdated->add($client);
    }

    public function preFlush(): void
    {
        foreach ($this->clientsToBeUpdated as $client) {
            $this->serviceOutageUpdater->updateClient($client);
        }

        $this->clientsToBeUpdated->clear();
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
    }

    public function rollback(): void
    {
        $this->clientsToBeUpdated->clear();
    }
}
