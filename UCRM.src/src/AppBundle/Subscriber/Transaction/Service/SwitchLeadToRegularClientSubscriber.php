<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Service;

use AppBundle\Entity\Service;
use AppBundle\Event\Service\ServiceAddEvent;
use AppBundle\Event\Service\ServiceEditEvent;
use AppBundle\Facade\ClientFacade;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SwitchLeadToRegularClientSubscriber implements EventSubscriberInterface
{
    /**
     * @var ClientFacade
     */
    private $clientFacade;

    public function __construct(ClientFacade $clientFacade)
    {
        $this->clientFacade = $clientFacade;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ServiceAddEvent::class => 'handleServiceAddEvent',
            ServiceEditEvent::class => 'handleServiceEditEvent',
        ];
    }

    public function handleServiceAddEvent(ServiceAddEvent $event): void
    {
        $this->processService($event->getService());
    }

    public function handleServiceEditEvent(ServiceEditEvent $event): void
    {
        // we're only worried about conversion from quoted
        if ($event->getServiceBeforeUpdate()->getStatus() !== Service::STATUS_QUOTED) {
            return;
        }

        $this->processService($event->getService());
    }

    private function processService(Service $service): void
    {
        if ($service->getStatus() === Service::STATUS_QUOTED || ! $service->getClient()->getIsLead()) {
            return;
        }

        $this->clientFacade->handleSwitchLead($service->getClient(), false);
    }
}
