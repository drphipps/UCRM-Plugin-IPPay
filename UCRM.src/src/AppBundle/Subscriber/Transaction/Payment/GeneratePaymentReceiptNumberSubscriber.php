<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Payment;

use AppBundle\Entity\Payment;
use AppBundle\Event\Payment\PaymentAddEvent;
use AppBundle\Event\Payment\PaymentEditEvent;
use AppBundle\RabbitMq\Payment\GeneratePaymentReceiptNumberMessage;
use Ds\Queue;
use Ds\Set;
use RabbitMqBundle\RabbitMqEnqueuer;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class GeneratePaymentReceiptNumberSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var RabbitMqEnqueuer
     */
    private $enqueuer;

    /**
     * @var Queue|Payment[]
     */
    private $paymentQueue;

    public function __construct(RabbitMqEnqueuer $enqueuer)
    {
        $this->enqueuer = $enqueuer;

        $this->paymentQueue = new Queue();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PaymentAddEvent::class => 'handlePaymentAddEvent',
            PaymentEditEvent::class => 'handlePaymentEditEvent',
        ];
    }

    public function handlePaymentAddEvent(PaymentAddEvent $event): void
    {
        $this->paymentQueue->push($event->getPayment());
    }

    public function handlePaymentEditEvent(PaymentEditEvent $event): void
    {
        if ($event->getPayment()->getReceiptNumber()) {
            return;
        }

        $this->paymentQueue->push($event->getPayment());
    }

    public function preFlush(): void
    {
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
        $paymentIds = new Set();
        foreach ($this->paymentQueue as $payment) {
            if (! $payment->getClient()) {
                continue;
            }

            $paymentIds->add($payment->getId());
        }

        if (! $paymentIds->isEmpty()) {
            $this->enqueuer->enqueue(new GeneratePaymentReceiptNumberMessage($paymentIds->toArray()));
        }
    }

    public function rollback(): void
    {
        $this->paymentQueue->clear();
    }
}
