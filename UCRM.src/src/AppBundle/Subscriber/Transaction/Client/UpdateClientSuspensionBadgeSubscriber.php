<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Client;

use AppBundle\Entity\Client;
use AppBundle\Entity\Service;
use AppBundle\Event\Service\ServiceEditEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UpdateClientSuspensionBadgeSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ServiceEditEvent::class => 'handleServiceEditEvent',
        ];
    }

    public function handleServiceEditEvent(ServiceEditEvent $event): void
    {
        $service = $event->getService();
        $service->calculateStatus();

        if (
            $event->getServiceBeforeUpdate()->getStatus() === Service::STATUS_SUSPENDED
            || $service->getStatus() === Service::STATUS_SUSPENDED
        ) {
            $this->updateClientBadge($service->getClient());
        }
    }

    private function updateClientBadge(Client $client): void
    {
        $hasSuspendedService = false;
        foreach ($client->getNotDeletedServices() as $service) {
            if ($service->getStatus() === Service::STATUS_SUSPENDED) {
                $hasSuspendedService = true;
            }
        }
        $client->setHasSuspendedService($hasSuspendedService);
    }
}
