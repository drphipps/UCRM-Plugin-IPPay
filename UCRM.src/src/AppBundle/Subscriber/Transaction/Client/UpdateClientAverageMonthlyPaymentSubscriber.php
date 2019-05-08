<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Client;

use AppBundle\Entity\Client;
use AppBundle\Entity\Service;
use AppBundle\Entity\Surcharge;
use AppBundle\Entity\Tariff;
use AppBundle\Event\Service\ServiceAddEvent;
use AppBundle\Event\Service\ServiceArchiveEvent;
use AppBundle\Event\Service\ServiceEditEvent;
use AppBundle\Event\ServiceSurcharge\ServiceSurchargeAddEvent;
use AppBundle\Event\ServiceSurcharge\ServiceSurchargeDeleteEvent;
use AppBundle\Event\ServiceSurcharge\ServiceSurchargeEditEvent;
use AppBundle\Event\Surcharge\SurchargeEditEvent;
use AppBundle\Event\Tariff\TariffEditEvent;
use AppBundle\Event\Tax\TaxAddEvent;
use AppBundle\Service\Client\ClientAverageMonthlyPaymentCalculator;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Ds\Set;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class UpdateClientAverageMonthlyPaymentSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var Set|Client[]
     */
    private $clientsToBeUpdated;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ClientAverageMonthlyPaymentCalculator
     */
    private $clientAverageMonthlyPaymentCalculator;

    public function __construct(
        EntityManager $entityManager,
        ClientAverageMonthlyPaymentCalculator $clientAverageMonthlyPaymentCalculator
    ) {
        $this->entityManager = $entityManager;
        $this->clientAverageMonthlyPaymentCalculator = $clientAverageMonthlyPaymentCalculator;
        $this->clientsToBeUpdated = new Set();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ServiceAddEvent::class => 'handleServiceAddEvent',
            ServiceEditEvent::class => 'handleServiceEditEvent',
            ServiceArchiveEvent::class => 'handleServiceArchiveEvent',
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
        $this->clientsToBeUpdated->add($event->getService()->getClient());
    }

    public function handleServiceEditEvent(ServiceEditEvent $event): void
    {
        $this->clientsToBeUpdated->add($event->getService()->getClient());
    }

    public function handleServiceArchiveEvent(ServiceArchiveEvent $event): void
    {
        $this->clientsToBeUpdated->add($event->getService()->getClient());
    }

    public function handleServiceSurchargeAddEvent(ServiceSurchargeAddEvent $event): void
    {
        $this->clientsToBeUpdated->add($event->getServiceSurcharge()->getService()->getClient());
    }

    public function handleServiceSurchargeEditEvent(ServiceSurchargeEditEvent $event): void
    {
        $this->clientsToBeUpdated->add($event->getServiceSurcharge()->getService()->getClient());
    }

    public function handleServiceSurchargeDeleteEvent(ServiceSurchargeDeleteEvent $event): void
    {
        $this->clientsToBeUpdated->add($event->getServiceSurcharge()->getService()->getClient());
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
            $this->clientsToBeUpdated->add($service->getClient());
        }
    }

    private function handleSurcharge(Surcharge $surcharge): void
    {
        foreach ($surcharge->getServiceSurcharges() as $serviceSurcharge) {
            $this->clientsToBeUpdated->add($serviceSurcharge->getService()->getClient());
        }
    }

    private function handleTariff(Tariff $tariff): void
    {
        foreach ($tariff->getServices() as $service) {
            $this->clientsToBeUpdated->add($service->getClient());
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
        foreach ($this->clientsToBeUpdated as $client) {
            $this->processClient($client);
        }

        $this->clientsToBeUpdated->clear();
    }

    public function rollback(): void
    {
        $this->clientsToBeUpdated->clear();
    }

    private function processClient(Client $client): void
    {
        if (! $client->getId() || $this->entityManager->getUnitOfWork()->isScheduledForDelete($client)) {
            return;
        }

        $client = $this->entityManager->find(Client::class, $client->getId());
        if (! $client) {
            return;
        }
        $this->entityManager->refresh($client);
        $this->clientAverageMonthlyPaymentCalculator->calculate($client);
        $this->entityManager->flush($client);
    }
}
