<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Client;

use AppBundle\Entity\Client;
use AppBundle\Event\Invoice\InvoiceAddEvent;
use AppBundle\Event\Invoice\InvoiceDeleteEvent;
use AppBundle\Event\Invoice\InvoiceEditEvent;
use AppBundle\Event\Payment\PaymentAddEvent;
use AppBundle\Event\Payment\PaymentDeleteEvent;
use AppBundle\Event\Payment\PaymentEditEvent;
use AppBundle\Event\Payment\PaymentUnmatchEvent;
use AppBundle\Event\Refund\RefundAddEvent;
use AppBundle\Event\Refund\RefundDeleteEvent;
use AppBundle\Service\Client\ClientAccountStandingsCalculator;
use Ds\Set;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class UpdateClientAccountStandingsSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var ClientAccountStandingsCalculator
     */
    private $clientAccountStandingsCalculator;

    /**
     * @var Set|Client[]
     */
    private $clientsToBeUpdated;

    public function __construct(ClientAccountStandingsCalculator $clientAccountStandingsCalculator)
    {
        $this->clientAccountStandingsCalculator = $clientAccountStandingsCalculator;
        $this->clientsToBeUpdated = new Set();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PaymentAddEvent::class => 'handlePaymentAddEvent',
            PaymentDeleteEvent::class => 'handlePaymentDeleteEvent',
            PaymentEditEvent::class => 'handlePaymentEditEvent',
            PaymentUnmatchEvent::class => 'handlePaymentUnmatchEvent',
            InvoiceAddEvent::class => 'handleInvoiceAddEvent',
            InvoiceEditEvent::class => 'handleInvoiceEditEvent',
            InvoiceDeleteEvent::class => 'handleInvoiceDeleteEvent',
            RefundAddEvent::class => 'handleRefundAddEvent',
            RefundDeleteEvent::class => 'handleRefundDeleteEvent',
        ];
    }

    public function handlePaymentAddEvent(PaymentAddEvent $event): void
    {
        if ($client = $event->getPayment()->getClient()) {
            $this->clientsToBeUpdated->add($client);
        }
    }

    public function handlePaymentDeleteEvent(PaymentDeleteEvent $event): void
    {
        if ($client = $event->getPayment()->getClient()) {
            $this->clientsToBeUpdated->add($client);
        }
    }

    public function handlePaymentEditEvent(PaymentEditEvent $event): void
    {
        $payment = $event->getPayment();
        $paymentBeforeUpdate = $event->getPaymentBeforeUpdate();

        if ($payment->getClient()) {
            $this->clientsToBeUpdated->add($payment->getClient());
        }

        if ($paymentBeforeUpdate->getClient()) {
            $this->clientsToBeUpdated->add($paymentBeforeUpdate->getClient());
        }
    }

    public function handlePaymentUnmatchEvent(PaymentUnmatchEvent $event): void
    {
        if ($client = $event->getClient()) {
            $this->clientsToBeUpdated->add($client);
        }
    }

    public function handleInvoiceAddEvent(InvoiceAddEvent $event): void
    {
        $client = $event->getInvoice()->getClient();
        $this->clientsToBeUpdated->add($client);
    }

    public function handleInvoiceEditEvent(InvoiceEditEvent $event): void
    {
        $client = $event->getInvoice()->getClient();
        $this->clientsToBeUpdated->add($client);
    }

    public function handleInvoiceDeleteEvent(InvoiceDeleteEvent $event): void
    {
        $client = $event->getInvoice()->getClient();
        $this->clientsToBeUpdated->add($client);
    }

    public function handleRefundAddEvent(RefundAddEvent $event): void
    {
        if ($client = $event->getRefund()->getClient()) {
            $this->clientsToBeUpdated->add($client);
        }
    }

    public function handleRefundDeleteEvent(RefundDeleteEvent $event): void
    {
        if ($client = $event->getRefund()->getClient()) {
            $this->clientsToBeUpdated->add($client);
        }
    }

    public function preFlush(): void
    {
        foreach ($this->clientsToBeUpdated as $client) {
            $this->clientAccountStandingsCalculator->calculate($client);
        }

        $this->clientsToBeUpdated->clear();
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
    }

    public function rollback(): void
    {
        $this->clientsToBeUpdated->clear();
    }
}
