<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Service\Facade;

use AppBundle\Entity\Client;
use AppBundle\Entity\User;
use AppBundle\Event\User\TicketCommentSeenEvent;
use Doctrine\ORM\EntityManagerInterface;
use RabbitMqBundle\RabbitMqEnqueuer;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Event\Job\JobEditEvent;
use TicketingBundle\Api\Map\TicketMap;
use TicketingBundle\DataProvider\TicketActivityDataProvider;
use TicketingBundle\DataProvider\TicketDataProvider;
use TicketingBundle\Entity\Ticket;
use TicketingBundle\Entity\TicketComment;
use TicketingBundle\Entity\TicketGroup;
use TicketingBundle\Event\Ticket\TicketAddEvent;
use TicketingBundle\Event\Ticket\TicketAddImapEvent;
use TicketingBundle\Event\Ticket\TicketDeleteEvent;
use TicketingBundle\Event\Ticket\TicketEditEvent;
use TicketingBundle\Form\Data\TicketAssignData;
use TicketingBundle\Form\Data\TicketNewData;
use TicketingBundle\Form\Data\TicketNewUserData;
use TicketingBundle\RabbitMq\Ticket\DeleteTicketMessage;
use TransactionEventsBundle\TransactionDispatcher;

class TicketFacade
{
    /**
     * @var TicketActivityDataProvider
     */
    private $ticketActivityDataProvider;

    /**
     * @var TicketDataProvider
     */
    private $ticketDataProvider;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    public function __construct(
        TicketActivityDataProvider $ticketActivityDataProvider,
        TicketDataProvider $ticketDataProvider,
        TransactionDispatcher $transactionDispatcher,
        RabbitMqEnqueuer $rabbitMqEnqueuer
    ) {
        $this->ticketActivityDataProvider = $ticketActivityDataProvider;
        $this->ticketDataProvider = $ticketDataProvider;
        $this->transactionDispatcher = $transactionDispatcher;
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;
    }

    /**
     * @param string[] $emails
     */
    public function deleteByEmailFromAddresses(array $emails): int
    {
        $tickets = $this->ticketDataProvider->getByEmailFromAddressToDelete($emails);

        $countTickets = count($tickets);
        if ($countTickets === 1) {
            $this->handleDelete(reset($tickets));

            return $countTickets;
        }

        foreach ($tickets as $ticket) {
            $this->rabbitMqEnqueuer->enqueue(new DeleteTicketMessage($ticket->getId()));
        }

        return $countTickets;
    }

    public function handleEdit(Ticket $ticket, Ticket $ticketBeforeUpdate): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($ticket, $ticketBeforeUpdate) {
                yield new TicketEditEvent($ticket, $ticketBeforeUpdate);
            }
        );
    }

    public function handleNewFromData(TicketNewData $data, Client $client, ?User $user = null): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($data, $client, $user) {
                $ticket = new Ticket();
                $ticket->setSubject($data->subject);
                $ticket->setClient($client);

                $ticketComment = new TicketComment();
                $ticketComment->setBody($data->message);
                $ticketComment->setTicket($ticket);
                if ($user) {
                    $ticketComment->setUser($user);
                }

                $ticket->addActivity($ticketComment);

                $entityManager->persist($ticket);

                yield new TicketAddEvent($ticket, $ticketComment, $data->attachmentFiles);
            }
        );
    }

    public function handleNewFromUserData(TicketNewUserData $data, User $user): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($data, $user) {
                $ticket = new Ticket();
                $ticket->setSubject($data->subject);
                $ticket->setClient($data->client);
                $ticket->setPublic(! $data->private);

                $ticketComment = new TicketComment();
                $ticketComment->setBody($data->message);
                $ticketComment->setTicket($ticket);
                $ticketComment->setUser($user);
                $ticketComment->setPublic(! $data->private);
                $ticket->addActivity($ticketComment);

                $entityManager->persist($ticket);

                yield new TicketAddEvent($ticket, $ticketComment, $data->attachmentFiles);
            }
        );
    }

    public function handleStatusChange(Ticket $ticket, int $status): void
    {
        $ticketBeforeUpdate = clone $ticket;
        $ticket->setStatus($status);
        $this->handleEdit($ticket, $ticketBeforeUpdate);
    }

    public function handleTicketGroupChange(Ticket $ticket, ?TicketGroup $ticketGroup): void
    {
        $ticketBeforeUpdate = clone $ticket;
        $ticket->setGroup($ticketGroup);
        $this->handleEdit($ticket, $ticketBeforeUpdate);
    }

    public function handleAddJob(Ticket $ticket, Job $job): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($ticket, $job) {
                $ticketBeforeUpdate = clone $ticket;
                $jobBeforeUpdate = clone $job;

                $ticket->addJob($job);

                yield new TicketEditEvent($ticket, $ticketBeforeUpdate);
                yield new JobEditEvent($job, $jobBeforeUpdate);
            }
        );
    }

    public function handleRemoveJob(Ticket $ticket, Job $job): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($ticket, $job) {
                $ticketBeforeUpdate = clone $ticket;
                $jobBeforeUpdate = clone $job;

                $ticket->removeJob($job);

                yield new TicketEditEvent($ticket, $ticketBeforeUpdate);
                yield new JobEditEvent($job, $jobBeforeUpdate);
            }
        );
    }

    public function handleNewFromAPI(Ticket $ticket, TicketMap $ticketMap): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($ticket, $ticketMap) {
                $entityManager->persist($ticket);

                if ($ticket->getEmailFromAddress()) {
                    yield new TicketAddImapEvent($ticket);
                }

                foreach ($ticketMap->activity as $activity) {
                    if ($activity->comment) {
                        $ticketComments = $ticket->getComments();
                        yield new TicketAddEvent(
                            $ticket,
                            $ticketComments->current(),
                            $activity->comment->attachments ?? []
                        );
                    }
                }
            }
        );
    }

    public function handleDelete(Ticket $ticket): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $em) use ($ticket) {
                $ticketId = $ticket->getId();
                $em->remove($ticket);

                yield new TicketDeleteEvent($ticket, $ticketId);
            }
        );
    }

    /**
     * @param Ticket[]|array $tickets
     *
     * @throws \Throwable
     */
    public function handleDeleteMultiple($tickets): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $em) use ($tickets) {
                foreach ($tickets as $ticket) {
                    $ticketId = $ticket->getId();
                    $em->remove($ticket);

                    yield new TicketDeleteEvent($ticket, $ticketId);
                }
            }
        );
    }

    public function handleAssign(Ticket $ticket, TicketAssignData $assignData): void
    {
        $ticketBeforeUpdate = clone $ticket;

        if ($ticket->getAssignedUser() !== $assignData->assignedUser) {
            $ticket->setAssignedUser($assignData->assignedUser);
        }

        if ($ticket->getClient() !== $assignData->assignedClient) {
            $ticket->setClient($assignData->assignedClient);
        }

        $this->handleEdit($ticket, $ticketBeforeUpdate);
    }

    public function handleTicketShown(Ticket $ticket, User $user): void
    {
        $ticketComment = $this->ticketActivityDataProvider->findLastTicketComment($ticket, (bool) $user->getClient());
        if (! $ticketComment) {
            return;
        }

        $this->transactionDispatcher->transactional(
            function () use ($user, $ticketComment) {
                yield new TicketCommentSeenEvent($user, $ticketComment);
            }
        );
    }
}
