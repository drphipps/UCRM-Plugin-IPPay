<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Event\Ticket;

use AppBundle\Entity\User;
use AppBundle\Entity\WebhookEvent;
use AppBundle\Interfaces\WebhookRequestableInterface;
use Symfony\Component\EventDispatcher\Event;
use TicketingBundle\Entity\Ticket;

final class TicketEditEvent extends Event implements WebhookRequestableInterface
{
    /**
     * @var Ticket
     */
    private $ticket;

    /**
     * @var Ticket
     */
    private $ticketBeforeUpdate;

    /**
     * @var User|null
     */
    private $user;

    public function __construct(Ticket $ticket, Ticket $ticketBeforeUpdate, ?User $user = null)
    {
        $this->ticket = $ticket;
        $this->ticketBeforeUpdate = $ticketBeforeUpdate;
        $this->user = $user;
    }

    public function getTicket(): Ticket
    {
        return $this->ticket;
    }

    public function getTicketBeforeUpdate(): Ticket
    {
        return $this->ticketBeforeUpdate;
    }

    /**
     * @return Ticket
     */
    public function getWebhookEntityBeforeEdit(): ?object
    {
        return $this->getTicketBeforeUpdate();
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getWebhookChangeType(): string
    {
        return WebhookEvent::EDIT;
    }

    public function getEventName(): string
    {
        return 'ticket.edit';
    }

    public function getWebhookEntityClass(): string
    {
        return 'ticket';
    }

    /**
     * @return Ticket
     */
    public function getWebhookEntity(): ?object
    {
        return $this->ticket;
    }

    public function getWebhookEntityId(): ?int
    {
        return $this->ticket->getId();
    }
}
