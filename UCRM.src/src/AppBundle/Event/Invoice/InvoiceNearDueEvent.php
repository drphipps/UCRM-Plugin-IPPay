<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Invoice;

use AppBundle\Entity\WebhookEvent;

final class InvoiceNearDueEvent extends AbstractInvoiceEvent
{
    public function getWebhookChangeType(): string
    {
        return WebhookEvent::NEAR_DUE;
    }

    public function getEventName(): string
    {
        return 'invoice.near_due';
    }
}
