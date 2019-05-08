<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Event\Ticket;

use AppBundle\Entity\WebhookEvent;
use AppBundle\Interfaces\WebhookRequestableInterface;
use Symfony\Component\EventDispatcher\Event;
use TicketingBundle\Entity\TicketStatusChange;

final class TicketStatusChangedEvent extends Event implements WebhookRequestableInterface
{
    /**
     * @var TicketStatusChange
     */
    private $ticketStatusChange;

    public function __construct(TicketStatusChange $ticketStatusChange)
    {
        $this->ticketStatusChange = $ticketStatusChange;
    }

    public function getTicketStatusChange(): TicketStatusChange
    {
        return $this->ticketStatusChange;
    }

    /**
     * The "ticketActivity" is correct, the webhook entity class should correspond with the way,
     * you can get the entity from API.
     */
    public function getWebhookEntityClass(): string
    {
        return 'ticketActivity';
    }

    /**
     * @return TicketStatusChange
     */
    public function getWebhookEntity(): ?object
    {
        return $this->ticketStatusChange;
    }

    public function getWebhookEntityBeforeEdit(): ?object
    {
        return null;
    }

    public function getWebhookChangeType(): string
    {
        return WebhookEvent::STATUS_CHANGE;
    }

    public function getWebhookEntityId(): ?int
    {
        return $this->ticketStatusChange->getId();
    }

    public function getEventName(): string
    {
        return 'ticket.status_change';
    }
}
