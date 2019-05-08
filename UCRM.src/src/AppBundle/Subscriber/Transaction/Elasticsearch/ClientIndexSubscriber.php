<?php
/*
 * @copyright Copyright (c) 2016 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Elasticsearch;

use AppBundle\Entity\Client;
use AppBundle\Event\Client\ClientAddEvent;
use AppBundle\Event\Client\ClientArchiveEvent;
use AppBundle\Event\Client\ClientDeleteEvent;
use AppBundle\Event\Client\ClientEditEvent;
use AppBundle\Event\Invoice\InvoiceAddEvent;
use AppBundle\Event\Invoice\InvoiceDeleteEvent;
use AppBundle\Event\Invoice\InvoiceEditEvent;
use AppBundle\Event\Quote\QuoteAddEvent;
use AppBundle\Event\Quote\QuoteDeleteEvent;
use AppBundle\Event\Quote\QuoteEditEvent;
use AppBundle\Event\Service\ServiceAddEvent;
use AppBundle\Event\Service\ServiceArchiveEvent;
use AppBundle\Event\Service\ServiceEditEvent;
use AppBundle\Event\ServiceDevice\ServiceDeviceAddEvent;
use AppBundle\Event\ServiceDevice\ServiceDeviceDeleteEvent;
use AppBundle\Event\ServiceDevice\ServiceDeviceEditEvent;
use AppBundle\Event\ServiceIp\ServiceIpAddEvent;
use AppBundle\Event\ServiceIp\ServiceIpDeleteEvent;
use FOS\ElasticaBundle\Persister\ObjectPersisterInterface;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class ClientIndexSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var ObjectPersisterInterface
     */
    private $objectPersister;

    /**
     * @var Client[]
     */
    private $clientsToInsert = [];

    /**
     * @var Client[]
     */
    private $clientsToUpdate = [];

    /**
     * @var int[]
     */
    private $clientsToDelete = [];

    public function __construct(ObjectPersisterInterface $objectPersister)
    {
        $this->objectPersister = $objectPersister;
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
            ClientAddEvent::class => 'handleClientAddEvent',
            ClientEditEvent::class => 'handleClientEditEvent',
            ClientArchiveEvent::class => 'handleClientArchiveEvent',
            ClientDeleteEvent::class => 'handleClientDeleteEvent',
            InvoiceAddEvent::class => 'handleInvoiceAddEvent',
            InvoiceEditEvent::class => 'handleInvoiceEditEvent',
            InvoiceDeleteEvent::class => 'handleInvoiceDeleteEvent',
            QuoteAddEvent::class => 'handleQuoteAddEvent',
            QuoteEditEvent::class => 'handleQuoteEditEvent',
            QuoteDeleteEvent::class => 'handleQuoteDeleteEvent',
        ];
    }

    public function handleServiceIpDeleteEvent(ServiceIpDeleteEvent $event): void
    {
        $service = $event->getServiceIp()->getServiceDevice()->getService();

        if ($service) {
            $this->replaceClientInIndex($service->getClient());
        }
    }

    public function handleServiceIpAddEvent(ServiceIpAddEvent $event): void
    {
        $service = $event->getServiceIp()->getServiceDevice()->getService();

        if ($service) {
            $this->replaceClientInIndex($service->getClient());
        }
    }

    public function handleServiceDeviceAddEvent(ServiceDeviceAddEvent $event): void
    {
        $service = $event->getServiceDevice()->getService();

        if ($service) {
            $this->replaceClientInIndex($service->getClient());
        }
    }

    public function handleServiceDeviceEditEvent(ServiceDeviceEditEvent $event): void
    {
        $service = $event->getServiceDevice()->getService();

        if ($service) {
            $this->replaceClientInIndex($service->getClient());
        }
    }

    public function handleServiceDeviceDeleteEvent(ServiceDeviceDeleteEvent $event): void
    {
        $service = $event->getServiceDevice()->getService();

        if ($service) {
            $this->replaceClientInIndex($service->getClient());
        }
    }

    public function handleServiceAddEvent(ServiceAddEvent $event): void
    {
        $this->replaceClientInIndex($event->getService()->getClient());
    }

    public function handleServiceEditEvent(ServiceEditEvent $event): void
    {
        $this->replaceClientInIndex($event->getService()->getClient());
    }

    public function handleServiceArchiveEvent(ServiceArchiveEvent $event): void
    {
        $this->replaceClientInIndex($event->getService()->getClient());
    }

    public function handleClientAddEvent(ClientAddEvent $event): void
    {
        $client = $event->getClient();
        $this->clientsToInsert[] = $client;
    }

    public function handleClientEditEvent(ClientEditEvent $event): void
    {
        $this->replaceClientInIndex($event->getClient());
    }

    public function handleClientArchiveEvent(ClientArchiveEvent $event): void
    {
        $this->replaceClientInIndex($event->getClient());
    }

    public function handleClientDeleteEvent(ClientDeleteEvent $event): void
    {
        $id = $event->getId();
        foreach ($this->clientsToInsert as $key => $client) {
            if ($client === $event->getClient()) {
                unset($this->clientsToInsert[$key]);

                break;
            }
        }
        unset($this->clientsToUpdate[$id]);
        $this->clientsToDelete[$id] = $id;
    }

    public function handleInvoiceAddEvent(InvoiceAddEvent $event): void
    {
        $this->replaceClientInIndex($event->getInvoice()->getClient());
    }

    public function handleInvoiceEditEvent(InvoiceEditEvent $event): void
    {
        $this->replaceClientInIndex($event->getInvoice()->getClient());
    }

    public function handleInvoiceDeleteEvent(InvoiceDeleteEvent $event): void
    {
        $this->replaceClientInIndex($event->getInvoice()->getClient());
    }

    public function handleQuoteAddEvent(QuoteAddEvent $event): void
    {
        $this->replaceClientInIndex($event->getQuote()->getClient());
    }

    public function handleQuoteEditEvent(QuoteEditEvent $event): void
    {
        $this->replaceClientInIndex($event->getQuote()->getClient());
    }

    public function handleQuoteDeleteEvent(QuoteDeleteEvent $event): void
    {
        $this->replaceClientInIndex($event->getQuote()->getClient());
    }

    private function replaceClientInIndex(Client $client): void
    {
        $this->clientsToUpdate[$client->getId()] = $client;
    }

    public function preFlush(): void
    {
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
        if ($this->clientsToInsert) {
            $this->objectPersister->insertMany($this->clientsToInsert);
            $this->clientsToInsert = [];
        }

        if ($this->clientsToUpdate) {
            $this->objectPersister->replaceMany($this->clientsToUpdate);
            $this->clientsToUpdate = [];
        }

        if ($this->clientsToDelete) {
            $this->objectPersister->deleteManyByIdentifiers($this->clientsToDelete);
            $this->clientsToDelete = [];
        }
    }

    public function rollback(): void
    {
        $this->clientsToInsert = [];
        $this->clientsToUpdate = [];
        $this->clientsToDelete = [];
    }
}
