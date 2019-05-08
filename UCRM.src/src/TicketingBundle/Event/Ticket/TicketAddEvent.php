<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Event\Ticket;

use AppBundle\Entity\WebhookEvent;

final class TicketAddEvent extends AbstractTicketEvent
{
    public function getWebhookChangeType(): string
    {
        return WebhookEvent::INSERT;
    }

    public function getEventName(): string
    {
        return 'ticket.add';
    }
}
