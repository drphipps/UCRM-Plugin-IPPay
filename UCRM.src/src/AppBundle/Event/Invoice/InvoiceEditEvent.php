<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Invoice;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\WebhookEvent;

final class InvoiceEditEvent extends AbstractInvoiceEvent
{
    /**
     * @var Invoice
     */
    private $invoiceBeforeUpdate;

    public function __construct(Invoice $invoice, Invoice $invoiceBeforeUpdate)
    {
        parent::__construct($invoice);
        $this->invoiceBeforeUpdate = $invoiceBeforeUpdate;
    }

    public function getInvoiceBeforeUpdate(): Invoice
    {
        return $this->invoiceBeforeUpdate;
    }

    /**
     * @return Invoice
     */
    public function getWebhookEntityBeforeEdit(): ?object
    {
        return $this->getInvoiceBeforeUpdate();
    }

    public function getWebhookChangeType(): string
    {
        return WebhookEvent::EDIT;
    }

    public function getEventName(): string
    {
        return 'invoice.edit';
    }
}
