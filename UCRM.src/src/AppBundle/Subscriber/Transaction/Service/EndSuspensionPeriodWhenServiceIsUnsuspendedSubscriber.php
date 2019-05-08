<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Service;

use AppBundle\Entity\Service;
use AppBundle\Event\Service\ServiceEditEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EndSuspensionPeriodWhenServiceIsUnsuspendedSubscriber implements EventSubscriberInterface
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
            && $service->getStatus() !== Service::STATUS_SUSPENDED
        ) {
            $this->endCurrentSuspensionPeriod($service);
        }
    }

    private function endCurrentSuspensionPeriod(Service $service): void
    {
        if ($suspensionPeriod = $service->getCurrentSuspensionPeriod()) {
            $suspensionPeriod->setUntil(new \DateTime());
        }
    }
}
