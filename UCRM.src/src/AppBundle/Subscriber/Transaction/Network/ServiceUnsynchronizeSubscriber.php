<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Network;

use AppBundle\Component\Sync\SynchronizationManager;
use AppBundle\Entity\Service;
use AppBundle\Event\Client\ClientDeleteEvent;
use AppBundle\Event\Service\ServiceAddEvent;
use AppBundle\Event\Service\ServiceArchiveEvent;
use AppBundle\Event\Service\ServiceEditEvent;
use AppBundle\Event\ServiceDevice\ServiceDeviceAddEvent;
use AppBundle\Event\ServiceDevice\ServiceDeviceDeleteEvent;
use AppBundle\Event\ServiceDevice\ServiceDeviceEditEvent;
use AppBundle\Event\ServiceIp\ServiceIpAddEvent;
use AppBundle\Event\ServiceIp\ServiceIpDeleteEvent;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class ServiceUnsynchronizeSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var SynchronizationManager
     */
    private $synchronizationManager;

    public function __construct(SynchronizationManager $synchronizationManager)
    {
        $this->synchronizationManager = $synchronizationManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ServiceIpDeleteEvent::class => 'handleServiceIpDeleteEvent',
            ServiceIpAddEvent::class => 'handleServiceIpAddEvent',
            ServiceDeviceAddEvent::class => 'handleServiceDeviceAddEvent',
            ServiceDeviceEditEvent::class => 'handleServiceDeviceEditEvent',
            ServiceDeviceDeleteEvent::class => 'handleServiceDeviceDeleteEvent',
            ServiceAddEvent::class => 'handleServiceAddEvent',
            ServiceEditEvent::class => 'handleServiceEditEvent',
            ServiceArchiveEvent::class => 'handleServiceArchiveEvent',
            ClientDeleteEvent::class => 'handleClientDeleteEvent',
        ];
    }

    public function handleClientDeleteEvent(ClientDeleteEvent $clientDeleteEvent)
    {
        foreach ($clientDeleteEvent->getClient()->getNotDeletedServices() as $service) {
            foreach ($service->getServiceDevices() as $serviceDevice) {
                if ($serviceDevice->getManagementIpAddress() !== null || ! $serviceDevice->getServiceIps()->isEmpty()) {
                    $this->unsynchronizeService($service);
                }
            }
        }
    }

    public function handleServiceIpDeleteEvent(ServiceIpDeleteEvent $event): void
    {
        $service = $event->getServiceIp()->getServiceDevice()->getService();

        if ($service) {
            $this->unsynchronizeService($service);
        }
    }

    public function handleServiceIpAddEvent(ServiceIpAddEvent $event): void
    {
        $service = $event->getServiceIp()->getServiceDevice()->getService();

        if ($service) {
            $this->unsynchronizeService($service);
        }
    }

    public function handleServiceDeviceAddEvent(ServiceDeviceAddEvent $event): void
    {
        $service = $event->getServiceDevice()->getService();

        if ($service) {
            $this->unsynchronizeService($service);
        }
    }

    public function handleServiceDeviceEditEvent(ServiceDeviceEditEvent $event): void
    {
        $service = $event->getServiceDevice()->getService();

        if ($service) {
            $this->unsynchronizeService($service);
        }
    }

    public function handleServiceDeviceDeleteEvent(ServiceDeviceDeleteEvent $event): void
    {
        $serviceDevice = $event->getServiceDevice();
        $service = $serviceDevice->getService();

        if (
            $service
            && (
                null !== $serviceDevice->getManagementIpAddress()
                || ! $serviceDevice->getServiceIps()->isEmpty()
            )
        ) {
            $this->unsynchronizeService($service);
        }
    }

    public function handleServiceAddEvent(ServiceAddEvent $event): void
    {
        $this->unsynchronizeService($event->getService());
    }

    public function handleServiceArchiveEvent(ServiceArchiveEvent $event): void
    {
        $this->unsynchronizeService($event->getService());
    }

    public function handleServiceEditEvent(ServiceEditEvent $event): void
    {
        $service = $event->getService();
        $service->calculateStatus();

        if (
            (
                $event->getServiceBeforeUpdate()->getStatus() !== Service::STATUS_SUSPENDED
                && $service->getStatus() === Service::STATUS_SUSPENDED
            )
            || (
                $event->getServiceBeforeUpdate()->getStatus() === Service::STATUS_SUSPENDED
                && $service->getStatus() !== Service::STATUS_SUSPENDED
            )
            || (
                $event->getServiceBeforeUpdate()->getStatus() !== Service::STATUS_ENDED
                && $service->getStatus() === Service::STATUS_ENDED
            )
        ) {
            $this->unsynchronizeService($service);
        }
    }

    private function unsynchronizeService(Service $service): void
    {
        $this->synchronizationManager->unsynchronizeService($service);
        $this->synchronizationManager->unsynchronizeSuspend();
    }

    public function preFlush(): void
    {
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
    }

    public function rollback(): void
    {
    }
}
