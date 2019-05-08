<?php
/*
 * @copyright Copyright (c) 2016 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Elasticsearch;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Event\Client\ClientEditEvent;
use FOS\ElasticaBundle\Persister\ObjectPersisterInterface;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class InvoiceIndexSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var ObjectPersisterInterface
     */
    private $objectPersister;

    /**
     * @var Invoice[]
     */
    private $invoicesToUpdate = [];

    public function __construct(ObjectPersisterInterface $objectPersister)
    {
        $this->objectPersister = $objectPersister;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ClientEditEvent::class => 'handleClientEditEvent',
        ];
    }

    public function handleClientEditEvent(ClientEditEvent $event): void
    {
        foreach ($event->getClient()->getInvoices() as $invoice) {
            $this->invoicesToUpdate[$invoice->getId()] = $invoice;
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
        if ($this->invoicesToUpdate) {
            $this->objectPersister->replaceMany($this->invoicesToUpdate);
            $this->invoicesToUpdate = [];
        }
    }

    public function rollback(): void
    {
        $this->invoicesToUpdate = [];
    }
}
