<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Invoice;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Payment;
use AppBundle\Event\Invoice\InvoiceEditEvent;
use AppBundle\Event\Payment\PaymentAddEvent;
use AppBundle\Event\Payment\PaymentEditEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UpdateInvoiceWhenPaymentIsMatchedSubscriber implements EventSubscriberInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
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
        if ($event->getPayment()->isMatched()) {
            $this->updateInvoice($event->getPayment());
        }
    }

    public function handlePaymentEditEvent(PaymentEditEvent $event): void
    {
        if (
            ! $event->getPaymentBeforeUpdate()->isMatched()
            && $event->getPayment()->isMatched()
        ) {
            $this->updateInvoice($event->getPayment());
        }
    }

    private function updateInvoice(Payment $payment): void
    {
        $fractionDigits = $payment->getCurrency()->getFractionDigits();

        foreach ($payment->getPaymentCovers() as $paymentCover) {
            $invoice = $paymentCover->getInvoice();
            $invoiceBeforeUpdate = clone $invoice;

            if (round($paymentCover->getAmount(), $fractionDigits) < round($invoice->getAmountToPay(), $fractionDigits)) {
                $invoice->setInvoiceStatus(Invoice::PARTIAL);
            } else {
                $invoice->setInvoiceStatus(Invoice::PAID);
                $invoice->setUncollectible(false);
            }

            $amountPaid = round($invoice->getAmountPaid() + $paymentCover->getAmount(), $fractionDigits);
            $invoice->setAmountPaid($amountPaid);

            $this->eventDispatcher->dispatch(
                InvoiceEditEvent::class,
                new InvoiceEditEvent(
                    $invoice,
                    $invoiceBeforeUpdate
                )
            );
        }
    }
}
