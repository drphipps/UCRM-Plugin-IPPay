<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Tariff;

use AppBundle\Event\Tariff\TariffEditEvent;
use AppBundle\Facade\ServiceFacade;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class RemoveServiceTaxesWhenTaxIsSetOnTariffSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var ServiceFacade
     */
    private $serviceFacade;

    public function __construct(ServiceFacade $serviceFacade)
    {
        $this->serviceFacade = $serviceFacade;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TariffEditEvent::class => 'handleTariffEditEvent',
        ];
    }

    public function handleTariffEditEvent(TariffEditEvent $event): void
    {
        if (
            $event->getTariff()->getTaxable()
            && $event->getTariff()->getTax()
            && (
                ! $event->getTariffBeforeUpdate()->getTaxable()
                || ! $event->getTariffBeforeUpdate()->getTax()
            )
        ) {
            $this->serviceFacade->handleRemoveTaxWhenTariffSuperior($event->getTariff());
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
    }

    public function rollback(): void
    {
    }
}
