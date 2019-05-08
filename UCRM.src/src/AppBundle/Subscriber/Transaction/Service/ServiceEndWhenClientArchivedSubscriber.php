<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Service;

use AppBundle\Event\Client\ClientArchiveEvent;
use AppBundle\Event\Service\ServiceEditEvent;
use AppBundle\Event\Service\ServiceEndEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ServiceEndWhenClientArchivedSubscriber implements EventSubscriberInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ClientArchiveEvent::class => 'handleClientArchiveEvent',
        ];
    }

    public function handleClientArchiveEvent(ClientArchiveEvent $event): void
    {
        $client = $event->getClient();

        $activeToLimit = new \DateTime('-1 day midnight');
        foreach ($client->getNotDeletedServices() as $service) {
            if ($service->getActiveTo() && $service->getActiveTo() <= $activeToLimit) {
                continue;
            }

            $serviceBeforeUpdate = clone $service;

            $service->setActiveTo(clone $activeToLimit);
            $service->calculateStatus();

            $this->eventDispatcher->dispatch(
                ServiceEditEvent::class,
                new ServiceEditEvent($service, $serviceBeforeUpdate)
            );
            $this->eventDispatcher->dispatch(
                ServiceEndEvent::class,
                new ServiceEndEvent($service)
            );
        }
    }
}
