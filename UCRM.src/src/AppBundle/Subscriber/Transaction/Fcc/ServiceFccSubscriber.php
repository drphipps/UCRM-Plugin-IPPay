<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Fcc;

use AppBundle\Entity\Country;
use AppBundle\Entity\Service;
use AppBundle\Event\Service\ServiceAddEvent;
use AppBundle\Event\Service\ServiceEditEvent;
use AppBundle\RabbitMq\Fcc\FccBlockIdMessage;
use Ds\Queue;
use RabbitMqBundle\RabbitMqEnqueuer;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class ServiceFccSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    /**
     * @var Queue
     */
    private $serviceQueue;

    public function __construct(RabbitMqEnqueuer $rabbitMqEnqueuer)
    {
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;

        $this->serviceQueue = new Queue();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ServiceEditEvent::class => 'handleServiceEditEvent',
            ServiceAddEvent::class => 'handleServiceAddEvent',
        ];
    }

    public function handleServiceAddEvent(ServiceAddEvent $event): void
    {
        if (! $event->getService()->getFccBlockId() && $this->isFromFccCountries($event->getService())) {
            $this->serviceQueue->push($event->getService());
        }
    }

    public function handleServiceEditEvent(ServiceEditEvent $event): void
    {
        $service = $event->getService();
        $serviceBeforeUpdate = $event->getServiceBeforeUpdate();

        if ($service->isDeleted() || $service->getClient()->isDeleted()) {
            return;
        }

        if (
            $service->getAddressGpsLon() !== $serviceBeforeUpdate->getAddressGpsLon()
            || $service->getAddressGpsLat() !== $serviceBeforeUpdate->getAddressGpsLat()
            || $service->getStreet1() !== $serviceBeforeUpdate->getStreet1()
            || $service->getStreet2() !== $serviceBeforeUpdate->getStreet2()
            || $service->getCity() !== $serviceBeforeUpdate->getCity()
            || $service->getCountry() !== $serviceBeforeUpdate->getCountry()
            || $service->getState() !== $serviceBeforeUpdate->getState()
            || $service->getZipCode() !== $serviceBeforeUpdate->getZipCode()
        ) {
            $service->setFccBlockId(
                $service->getFccBlockId() !== $serviceBeforeUpdate->getFccBlockId()
                    ? $service->getFccBlockId()
                    : null
            );
        }

        if (! $service->getFccBlockId() && $this->isFromFccCountries($service)) {
            $this->serviceQueue->push($service);
        }
    }

    public function preFlush(): void
    {
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
        /** @var Service $service */
        foreach ($this->serviceQueue as $service) {
            $this->rabbitMqEnqueuer->enqueue(new FccBlockIdMessage($service));
        }
    }

    public function rollback(): void
    {
        $this->serviceQueue->clear();
    }

    private function isFromFccCountries(Service $service): bool
    {
        return ($country = $service->getCountry())
            && in_array($country->getId(), Country::FCC_COUNTRIES, true);
    }
}
