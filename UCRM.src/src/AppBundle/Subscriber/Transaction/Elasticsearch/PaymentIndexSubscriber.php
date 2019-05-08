<?php
/*
 * @copyright Copyright (c) 2016 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Elasticsearch;

use AppBundle\Entity\Payment;
use AppBundle\Event\Client\ClientEditEvent;
use FOS\ElasticaBundle\Persister\ObjectPersisterInterface;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class PaymentIndexSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var ObjectPersisterInterface
     */
    private $objectPersister;

    /**
     * @var Payment[]
     */
    private $paymentsToUpdate = [];

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
        foreach ($event->getClient()->getPayments() as $payment) {
            $this->paymentsToUpdate[] = $payment;
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
        if ($this->paymentsToUpdate) {
            $this->objectPersister->replaceMany($this->paymentsToUpdate);
            $this->paymentsToUpdate = [];
        }
    }

    public function rollback(): void
    {
        $this->paymentsToUpdate = [];
    }
}
