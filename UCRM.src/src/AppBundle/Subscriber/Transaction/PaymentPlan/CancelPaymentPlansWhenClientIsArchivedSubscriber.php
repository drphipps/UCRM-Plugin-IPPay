<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\PaymentPlan;

use AppBundle\Entity\Client;
use AppBundle\Event\Client\ClientArchiveEvent;
use AppBundle\Facade\PaymentPlanFacade;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class CancelPaymentPlansWhenClientIsArchivedSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var PaymentPlanFacade
     */
    private $paymentPlanFacade;

    /**
     * @var Client[]
     */
    private $clientsToHandle = [];

    public function __construct(PaymentPlanFacade $paymentPlanFacade)
    {
        $this->paymentPlanFacade = $paymentPlanFacade;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ClientArchiveEvent::class => 'handleClientArchiveEvent',
        ];
    }

    public function handleClientArchiveEvent(ClientArchiveEvent $event): void
    {
        $client = $event->getClient();
        if (! $client->getActivePaymentPlans()->isEmpty()) {
            $this->clientsToHandle[$client->getId()] = $client;
        }
    }

    public function preFlush(): void
    {
        foreach ($this->clientsToHandle as $client) {
            $this->paymentPlanFacade->unsubscribeMultiple($client->getActivePaymentPlans()->toArray());
        }

        $this->clientsToHandle = [];
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
    }

    public function rollback(): void
    {
        $this->clientsToHandle = [];
    }
}
