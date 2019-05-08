<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\PaymentPlan;

use AppBundle\Entity\Service;
use AppBundle\Entity\Surcharge;
use AppBundle\Entity\Tariff;
use AppBundle\Event\Service\ServiceAddEvent;
use AppBundle\Event\Service\ServiceEditEvent;
use AppBundle\Event\ServiceSurcharge\ServiceSurchargeAddEvent;
use AppBundle\Event\ServiceSurcharge\ServiceSurchargeDeleteEvent;
use AppBundle\Event\ServiceSurcharge\ServiceSurchargeEditEvent;
use AppBundle\Event\Surcharge\SurchargeEditEvent;
use AppBundle\Event\Tariff\TariffEditEvent;
use AppBundle\Event\Tax\TaxAddEvent;
use AppBundle\RabbitMq\PaymentPlan\UpdatePaymentPlanWhenChangedMessage;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Ds\Set;
use RabbitMqBundle\RabbitMqEnqueuer;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

/**
 * This subscriber handles updating client subscriptions (payment plans) linked to a service (autopay).
 * When service price changes in any way, the plan has to be automatically updated.
 *
 * This operation can be costly in case of Tax replacement or Surcharge price change, as we need to go through
 * every entity, that inherits the info in any way.
 * In case of tax replacement this could be improved by yielding edit events, when the tax is replaced,
 * however it would take a lot of time to change and test, so performance cost is preferable in this case.
 */
class UpdatePaymentPlanAmountWhenServicePriceChangesSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    /**
     * @var Set|Service[]
     */
    private $services;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        RabbitMqEnqueuer $rabbitMqEnqueuer,
        EntityManagerInterface $entityManager
    ) {
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;
        $this->entityManager = $entityManager;
        $this->services = new Set();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ServiceAddEvent::class => 'handleServiceAddEvent',
            ServiceEditEvent::class => 'handleServiceEditEvent',
            ServiceSurchargeAddEvent::class => 'handleServiceSurchargeAddEvent',
            ServiceSurchargeEditEvent::class => 'handleServiceSurchargeEditEvent',
            ServiceSurchargeDeleteEvent::class => 'handleServiceSurchargeDeleteEvent',
            TariffEditEvent::class => 'handleTariffEditEvent',
            SurchargeEditEvent::class => 'handleSurchargeEditEvent',
            TaxAddEvent::class => 'handleTaxAddEvent',
        ];
    }

    public function handleServiceAddEvent(ServiceAddEvent $event): void
    {
        if (! $event->getSupersededService()) {
            return;
        }

        $this->handleService($event->getService());
    }

    public function handleServiceEditEvent(ServiceEditEvent $event): void
    {
        $this->handleService($event->getService());

        if ($supersededService = $event->getService()->getSupersededByService()) {
            $this->handleService($supersededService);
        }
    }

    public function handleServiceSurchargeAddEvent(ServiceSurchargeAddEvent $event): void
    {
        $this->handleService($event->getServiceSurcharge()->getService());
    }

    public function handleServiceSurchargeEditEvent(ServiceSurchargeEditEvent $event): void
    {
        $this->handleService($event->getServiceSurcharge()->getService());
    }

    public function handleServiceSurchargeDeleteEvent(ServiceSurchargeDeleteEvent $event): void
    {
        $this->handleService($event->getServiceSurcharge()->getService());
    }

    public function handleTariffEditEvent(TariffEditEvent $event): void
    {
        $this->handleTariff($event->getTariff());
    }

    public function handleSurchargeEditEvent(SurchargeEditEvent $event): void
    {
        $this->handleSurcharge($event->getSurcharge());
    }

    public function handleTaxAddEvent(TaxAddEvent $event): void
    {
        // Only handle Tax replacements.
        if (! $event->getSupersededTax()) {
            return;
        }

        $taxBeingReplaced = $event->getSupersededTax();

        $surcharges = $this->entityManager->getRepository(Surcharge::class)->findBy(
            [
                'tax' => $taxBeingReplaced,
            ]
        );
        foreach ($surcharges as $surcharge) {
            $this->handleSurcharge($surcharge);
        }

        $tariffs = $this->entityManager->getRepository(Tariff::class)->findBy(
            [
                'tax' => $taxBeingReplaced,
            ]
        );
        foreach ($tariffs as $tariff) {
            $this->handleTariff($tariff);
        }

        /** @var Service[] $services */
        $services = $this->entityManager->getRepository(Service::class)->matching(
            Criteria::create()
                ->orWhere(Criteria::expr()->eq('tax1', $taxBeingReplaced))
                ->orWhere(Criteria::expr()->eq('tax2', $taxBeingReplaced))
                ->orWhere(Criteria::expr()->eq('tax3', $taxBeingReplaced))
        );
        foreach ($services as $service) {
            $this->handleService($service);
        }
    }

    private function handleSurcharge(Surcharge $surcharge): void
    {
        foreach ($surcharge->getServiceSurcharges() as $serviceSurcharge) {
            $this->handleService($serviceSurcharge->getService());
        }
    }

    private function handleTariff(Tariff $tariff): void
    {
        foreach ($tariff->getServices() as $service) {
            $this->handleService($service);
        }
    }

    private function handleService(Service $service): void
    {
        if (! $service->isDeleted()) {
            $this->services->add($service);
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
        foreach ($this->services as $service) {
            $this->rabbitMqEnqueuer->enqueue(new UpdatePaymentPlanWhenChangedMessage($service->getId()));
        }

        $this->services->clear();
    }

    public function rollback(): void
    {
        $this->services->clear();
    }
}
