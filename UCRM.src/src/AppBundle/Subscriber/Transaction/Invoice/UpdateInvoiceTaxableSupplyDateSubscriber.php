<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Invoice;

use AppBundle\Event\Invoice\AbstractInvoiceEvent;
use AppBundle\Event\Invoice\InvoiceAddEvent;
use AppBundle\Event\Invoice\InvoiceEditEvent;
use AppBundle\Service\Financial\InvoiceTaxableSupplyDateCalculator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UpdateInvoiceTaxableSupplyDateSubscriber implements EventSubscriberInterface
{
    /**
     * @var InvoiceTaxableSupplyDateCalculator
     */
    private $invoiceTaxableSupplyDateCalculator;

    public function __construct(InvoiceTaxableSupplyDateCalculator $invoiceTaxableSupplyDateCalculator)
    {
        $this->invoiceTaxableSupplyDateCalculator = $invoiceTaxableSupplyDateCalculator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            InvoiceAddEvent::class => 'handleInvoiceEvent',
            InvoiceEditEvent::class => 'handleInvoiceEvent',
        ];
    }

    public function handleInvoiceEvent(AbstractInvoiceEvent $event): void
    {
        $invoice = $event->getInvoice();
        $invoice->setTaxableSupplyDate($this->invoiceTaxableSupplyDateCalculator->computeTaxableSupplyDate($invoice));
    }
}
