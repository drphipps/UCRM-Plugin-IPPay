<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Subscriber\Transaction\Ticket;

use Ds\Queue;
use TicketingBundle\Entity\TicketComment;
use TicketingBundle\Event\Ticket\TicketAddEvent;
use TicketingBundle\Event\TicketComment\TicketCommentAddEvent;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class TicketLastCommentSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var Queue|TicketComment[]
     */
    private $ticketComments;

    public function __construct()
    {
        $this->ticketComments = new Queue();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TicketAddEvent::class => 'handleAddTicketEvent',
            TicketCommentAddEvent::class => 'handleAddTicketCommentEvent',
        ];
    }

    public function handleAddTicketEvent(TicketAddEvent $event): void
    {
        if ($event->getTicketComment()) {
            $this->ticketComments->push($event->getTicketComment());
        }
    }

    public function handleAddTicketCommentEvent(TicketCommentAddEvent $event): void
    {
        $this->ticketComments->push($event->getTicketComment());
    }

    public function preFlush(): void
    {
        foreach ($this->ticketComments as $ticketComment) {
            $ticketComment->getTicket()->setLastCommentAt(clone $ticketComment->getCreatedAt());
        }
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
    }

    public function rollback(): void
    {
        $this->ticketComments->clear();
    }
}
