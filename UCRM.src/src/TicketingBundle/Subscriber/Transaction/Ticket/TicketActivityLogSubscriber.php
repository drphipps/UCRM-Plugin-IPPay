<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Subscriber\Transaction\Ticket;

use ApiBundle\Security\AppKeyUser;
use AppBundle\Entity\User;
use SchedulingBundle\Entity\Job;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use TicketingBundle\Entity\Ticket;
use TicketingBundle\Entity\TicketActivityAssignmentInterface;
use TicketingBundle\Entity\TicketClientAssignment;
use TicketingBundle\Entity\TicketGroupAssignment;
use TicketingBundle\Entity\TicketJobAssignment;
use TicketingBundle\Entity\TicketStatusChange;
use TicketingBundle\Entity\TicketUserAssignment;
use TicketingBundle\Event\Ticket\TicketEditEvent;
use TicketingBundle\Event\Ticket\TicketStatusChangedEvent;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class TicketActivityLogSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(TokenStorageInterface $tokenStorage, EventDispatcherInterface $eventDispatcher)
    {
        $this->tokenStorage = $tokenStorage;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TicketEditEvent::class => 'handleEditTicketEvent',
        ];
    }

    public function handleEditTicketEvent(TicketEditEvent $event): void
    {
        $this->detectActivity($event->getTicket(), $event->getTicketBeforeUpdate());
    }

    public function preFlush(): void
    {
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
    }

    public function rollback(): void
    {
    }

    private function detectActivity(Ticket $ticket, Ticket $ticketBeforeUpdate): void
    {
        if ($ticket->getStatus() !== $ticketBeforeUpdate->getStatus()) {
            $this->handleStatusChange($ticket, $ticketBeforeUpdate);
        }

        if ($ticket->getGroup() !== $ticketBeforeUpdate->getGroup()) {
            $this->handleGroupChange($ticket);
        }

        $newJobs = $ticket->getJobs()->filter(
            function (Job $job) use ($ticketBeforeUpdate) {
                return ! $ticketBeforeUpdate->getJobs()->contains($job);
            }
        );
        foreach ($newJobs as $job) {
            $this->handleJobAssignment($ticket, $job, TicketJobAssignment::TYPE_ADD);
        }

        $removedJobs = $ticketBeforeUpdate->getJobs()->filter(
            function (Job $job) use ($ticket) {
                return ! $ticket->getJobs()->contains($job);
            }
        );
        foreach ($removedJobs as $job) {
            $this->handleJobAssignment($ticket, $job, TicketJobAssignment::TYPE_REMOVE);
        }

        if ($ticket->getAssignedUser() !== $ticketBeforeUpdate->getAssignedUser()) {
            $this->handleUserAssignment($ticket);
        }

        if ($ticket->getClient() !== $ticketBeforeUpdate->getClient()) {
            $this->handleClientAssignment($ticket);
        }
    }

    private function handleStatusChange(Ticket $ticket, Ticket $ticketBeforeUpdate): void
    {
        $statusChange = new TicketStatusChange();
        $statusChange->setTicket($ticket);
        $user = $this->getUser();
        if ($user instanceof User) {
            $statusChange->setUser($user);
        } elseif ($user instanceof AppKeyUser) {
            $statusChange->setAppKey($user->getAppKey());
        }
        $statusChange->setStatus($ticket->getStatus());
        $statusChange->setPublic(false);
        $statusChange->setPreviousStatus($ticketBeforeUpdate->getStatus());

        $ticket->addActivity($statusChange);

        $this->eventDispatcher->dispatch(
            TicketStatusChangedEvent::class,
            new TicketStatusChangedEvent(
                $statusChange
            )
        );
    }

    private function handleGroupChange(Ticket $ticket): void
    {
        $assignment = new TicketGroupAssignment();
        $assignment->setTicket($ticket);
        $user = $this->getUser();
        if ($user instanceof User) {
            $assignment->setUser($user);
        } elseif ($user instanceof AppKeyUser) {
            $assignment->setAppKey($user->getAppKey());
        }
        $assignment->setAssignedGroup($ticket->getGroup());
        $assignment->setPublic(false);
        $assignment->setType(
            $ticket->getGroup() === null
                ? TicketActivityAssignmentInterface::TYPE_REMOVE
                : TicketActivityAssignmentInterface::TYPE_ADD
        );

        $ticket->addActivity($assignment);
    }

    private function handleJobAssignment(Ticket $ticket, Job $job, string $type): void
    {
        $assignment = new TicketJobAssignment();
        $assignment->setTicket($ticket);
        $user = $this->getUser();
        if ($user instanceof User) {
            $assignment->setUser($user);
        } elseif ($user instanceof AppKeyUser) {
            $assignment->setAppKey($user->getAppKey());
        }
        $assignment->setAssignedJob($job);
        $assignment->setType($type);
        $assignment->setPublic(false);

        $ticket->addActivity($assignment);
    }

    private function handleUserAssignment(Ticket $ticket): void
    {
        $assignment = new TicketUserAssignment();
        $assignment->setTicket($ticket);
        $user = $this->getUser();
        if ($user instanceof User) {
            $assignment->setUser($user);
        } elseif ($user instanceof AppKeyUser) {
            $assignment->setAppKey($user->getAppKey());
        }
        $assignment->setAssignedUser($ticket->getAssignedUser());
        $assignment->setPublic(false);
        $assignment->setType(
            $ticket->getAssignedUser() === null
                ? TicketActivityAssignmentInterface::TYPE_REMOVE
                : TicketActivityAssignmentInterface::TYPE_ADD
        );

        $ticket->addActivity($assignment);
    }

    private function handleClientAssignment(Ticket $ticket): void
    {
        $assignment = new TicketClientAssignment();
        $assignment->setTicket($ticket);
        $user = $this->getUser();
        if ($user instanceof User) {
            $assignment->setUser($user);
        } elseif ($user instanceof AppKeyUser) {
            $assignment->setAppKey($user->getAppKey());
        }
        $assignment->setAssignedClient($ticket->getClient());
        $assignment->setPublic(false);
        $assignment->setType(
            $ticket->getClient() === null
                ? TicketActivityAssignmentInterface::TYPE_REMOVE
                : TicketActivityAssignmentInterface::TYPE_ADD
        );

        $ticket->addActivity($assignment);
    }

    private function getUser(): ?UserInterface
    {
        $token = $this->tokenStorage->getToken();
        if (! $token) {
            return null;
        }

        $user = $token->getUser();

        return $user instanceof UserInterface ? $user : null;
    }
}
