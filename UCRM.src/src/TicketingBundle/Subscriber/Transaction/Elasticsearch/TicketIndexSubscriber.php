<?php
/*
 * @copyright Copyright (c) 2016 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Subscriber\Transaction\Elasticsearch;

use FOS\ElasticaBundle\Persister\ObjectPersisterInterface;
use TicketingBundle\Entity\Ticket;
use TicketingBundle\Event\Ticket\TicketAddEvent;
use TicketingBundle\Event\Ticket\TicketDeleteEvent;
use TicketingBundle\Event\Ticket\TicketEditEvent;
use TicketingBundle\Event\Ticket\TicketStatusChangedEvent;
use TicketingBundle\Event\TicketComment\TicketCommentAddEvent;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class TicketIndexSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var ObjectPersisterInterface
     */
    private $objectPersister;

    /**
     * @var Ticket[]
     */
    private $ticketsToInsert = [];

    /**
     * @var Ticket[]
     */
    private $ticketsToUpdate = [];

    /**
     * @var int[]
     */
    private $ticketsToDelete = [];

    public function __construct(ObjectPersisterInterface $objectPersister)
    {
        $this->objectPersister = $objectPersister;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TicketAddEvent::class => 'handleTicketAddEvent',
            TicketEditEvent::class => 'handleTicketEditEvent',
            TicketStatusChangedEvent::class => 'handleTicketStatusChangedEvent',
            TicketCommentAddEvent::class => 'handleTicketCommentAddEvent',
        ];
    }

    public function handleTicketAddEvent(TicketAddEvent $event): void
    {
        $ticket = $event->getTicket();
        $this->ticketsToInsert[$ticket->getId()] = $ticket;
    }

    public function handleTicketEditEvent(TicketEditEvent $event): void
    {
        $this->replaceTicketInIndex($event->getTicket());
    }

    public function handleTicketStatusChangedEvent(TicketStatusChangedEvent $event): void
    {
        $this->replaceTicketInIndex($event->getTicketStatusChange()->getTicket());
    }

    public function handleTicketCommentAddEvent(TicketCommentAddEvent $event): void
    {
        $this->replaceTicketInIndex($event->getTicketComment()->getTicket());
    }

    public function handleTicketDeleteEvent(TicketDeleteEvent $event): void
    {
        $id = $event->getId();
        unset($this->ticketsToInsert[$id]);
        unset($this->ticketsToUpdate[$id]);
        $this->ticketsToDelete[$id] = $id;
    }

    private function replaceTicketInIndex(Ticket $ticket): void
    {
        $this->ticketsToUpdate[$ticket->getId()] = $ticket;
    }

    public function preFlush(): void
    {
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
        if ($this->ticketsToInsert) {
            $this->objectPersister->insertMany($this->ticketsToInsert);
            $this->ticketsToInsert = [];
        }

        if ($this->ticketsToUpdate) {
            $this->objectPersister->replaceMany($this->ticketsToUpdate);
            $this->ticketsToUpdate = [];
        }

        if ($this->ticketsToDelete) {
            $this->objectPersister->deleteManyByIdentifiers($this->ticketsToDelete);
            $this->ticketsToDelete = [];
        }
    }

    public function rollback(): void
    {
        $this->ticketsToInsert = [];
        $this->ticketsToUpdate = [];
        $this->ticketsToDelete = [];
    }
}
