<?php
/*
 * @copyright Copyright (c) 2019 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Invoice;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\WebhookEvent;

final class InvoiceDraftApprovedEvent extends AbstractInvoiceEvent
{
    /**
     * @var Invoice|null
     */
    private $invoiceBeforeUpdate;

    public function getWebhookChangeType(): string
    {
        return WebhookEvent::DRAFT_APPROVED;
    }

    public function getEventName(): string
    {
        return 'invoice.draft_approved';
    }

    public function __construct(Invoice $invoice, ?Invoice $invoiceBeforeUpdate)
    {
        parent::__construct($invoice);
        $this->invoiceBeforeUpdate = $invoiceBeforeUpdate;
    }

    public function getInvoiceBeforeUpdate(): ?Invoice
    {
        return $this->invoiceBeforeUpdate;
    }

    /**
     * @return Invoice|null
     */
    public function getWebhookEntityBeforeEdit(): ?object
    {
        return $this->getInvoiceBeforeUpdate();
    }
}
