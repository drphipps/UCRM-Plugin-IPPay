<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Payment;

use AppBundle\Entity\Payment;
use AppBundle\Event\Payment\PaymentEditEvent;
use AppBundle\Event\Payment\PaymentUnmatchEvent;
use AppBundle\Exception\TemplateRenderException;
use AppBundle\Handler\Payment\PdfHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UpdatePaymentReceiptPdfSubscriber implements EventSubscriberInterface
{
    /**
     * @var PdfHandler
     */
    private $pdfHandler;

    public function __construct(PdfHandler $pdfHandler)
    {
        $this->pdfHandler = $pdfHandler;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PaymentEditEvent::class => 'handlePaymentEditEvent',
            PaymentUnmatchEvent::class => 'handlePaymentUnmatchEvent',
        ];
    }

    public function handlePaymentEditEvent(PaymentEditEvent $event): void
    {
        $this->handlePayment($event->getPayment());
    }

    public function handlePaymentUnmatchEvent(PaymentUnmatchEvent $event): void
    {
        $this->handlePayment($event->getPayment());
    }

    private function handlePayment(Payment $payment): void
    {
        if (! $payment->getClient()) {
            $this->pdfHandler->deletePaymentReceiptPdf($payment);

            return;
        }

        try {
            $this->pdfHandler->savePaymentReceiptPdf($payment);
        } catch (TemplateRenderException | \Dompdf\Exception $exception) {
            // silently ignore, notification about fail is already handled in savePaymentReceiptPdf
        }
    }
}
