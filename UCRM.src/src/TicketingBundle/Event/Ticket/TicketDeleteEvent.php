<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Event\Ticket;

use AppBundle\Entity\WebhookEvent;
use TicketingBundle\Entity\Ticket;

final class TicketDeleteEvent extends AbstractTicketEvent
{
    /**
     * @var int
     */
    private $id;

    public function __construct(Ticket $ticket, int $id)
    {
        parent::__construct($ticket);
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getWebhookChangeType(): string
    {
        return WebhookEvent::DELETE;
    }

    public function getEventName(): string
    {
        return 'ticket.delete';
    }
}
