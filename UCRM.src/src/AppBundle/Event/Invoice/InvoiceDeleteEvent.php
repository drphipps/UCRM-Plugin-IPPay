<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Invoice;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\WebhookEvent;

final class InvoiceDeleteEvent extends AbstractInvoiceEvent
{
    /**
     * @var int
     */
    private $id;

    public function __construct(Invoice $invoice, int $id)
    {
        parent::__construct($invoice);
        $this->id = $id;
    }

    public function getWebhookChangeType(): string
    {
        return WebhookEvent::DELETE;
    }

    public function getWebhookEntityId(): ?int
    {
        return $this->id;
    }

    public function getEventName(): string
    {
        return 'invoice.delete';
    }
}
