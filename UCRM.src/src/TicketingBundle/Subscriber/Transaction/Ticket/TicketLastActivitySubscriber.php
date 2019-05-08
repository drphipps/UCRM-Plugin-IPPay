<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Subscriber\Transaction\Ticket;

use Ds\Set;
use TicketingBundle\Entity\Ticket;
use TicketingBundle\Event\Ticket\TicketAddEvent;
use TicketingBundle\Event\Ticket\TicketEditEvent;
use TicketingBundle\Event\Ticket\TicketStatusChangedEvent;
use TicketingBundle\Event\TicketComment\TicketCommentAddEvent;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class TicketLastActivitySubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var Set|Ticket[]
     */
    private $lastActivityByClientTickets;

    /**
     * @var Set|Ticket[]
     */
    private $newTickets;

    /**
     * @var Set|Ticket[]
     */
    private $tickets;

    public function __construct()
    {
        $this->lastActivityByClientTickets = new Set();
        $this->newTickets = new Set();
        $this->tickets = new Set();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TicketAddEvent::class => 'handleAddTicketEvent',
            TicketEditEvent::class => 'handleEditTicketEvent',
            TicketStatusChangedEvent::class => 'handleTicketStatusChangedEvent',
            TicketCommentAddEvent::class => 'handleAddTicketCommentEvent',
        ];
    }

    public function handleAddTicketEvent(TicketAddEvent $event): void
    {
        $ticket = $event->getTicket();
        $this->tickets->add($ticket);
        $this->newTickets->add($ticket);

        if (
            $event->getTicketComment()
            && ! $event->getTicketComment()->getUser()
        ) {
            $this->lastActivityByClientTickets->add($ticket);
        }
    }

    public function handleEditTicketEvent(TicketEditEvent $event): void
    {
        $ticket = $event->getTicket();
        $this->tickets->add($ticket);
    }

    public function handleTicketStatusChangedEvent(TicketStatusChangedEvent $event): void
    {
        $ticket = $event->getTicketStatusChange()->getTicket();
        $this->tickets->add($ticket);
    }

    public function handleAddTicketCommentEvent(TicketCommentAddEvent $event): void
    {
        $ticket = $event->getTicketComment()->getTicket();
        $this->tickets->add($ticket);

        if (! $event->getTicketComment()->getUser()) {
            $this->lastActivityByClientTickets->add($ticket);
        }
    }

    public function preFlush(): void
    {
        foreach ($this->tickets as $ticket) {
            $ticket->setLastActivity($this->getLastActivity($ticket));
            $ticket->setIsLastActivityByClient($this->lastActivityByClientTickets->contains($ticket));
        }

        $this->tickets->clear();
        $this->newTickets->clear();
        $this->lastActivityByClientTickets->clear();
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
    }

    public function rollback(): void
    {
        $this->tickets->clear();
        $this->newTickets->clear();
        $this->lastActivityByClientTickets->clear();
    }

    private function getLastActivity(Ticket $ticket): \DateTime
    {
        // In case creating new ticket from IMAP import, use createdAt which is set in TicketMailFacade::addNotFoundTicket()
        return $this->newTickets->contains($ticket) && $ticket->getEmailFromAddress() && $ticket->getCreatedAt()
            ? $ticket->getCreatedAt()
            : new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
