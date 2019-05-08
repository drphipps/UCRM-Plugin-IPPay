<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 *
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\User;

use AppBundle\Entity\LastSeenTicketComment;
use AppBundle\Event\User\TicketCommentSeenEvent;
use Doctrine\ORM\EntityManager;
use Ds\Queue;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class TicketCommentSeenSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var Queue|TicketCommentSeenEvent[]
     */
    private $events;

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->events = new Queue();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TicketCommentSeenEvent::class => 'handleUserSeeTicketCommentEvent',
        ];
    }

    public function handleUserSeeTicketCommentEvent(TicketCommentSeenEvent $event): void
    {
        $this->events->push($event);
    }

    public function preFlush(): void
    {
        foreach ($this->events as $event) {
            $lastSeenTicketComment = $this->entityManager->getRepository(LastSeenTicketComment::class)
                ->findOneBy(
                    [
                        'user' => $event->getUser(),
                        'ticket' => $event->getTicketComment()->getTicket(),
                    ]
                );

            if (! $lastSeenTicketComment) {
                $lastSeenTicketComment = new LastSeenTicketComment();
                $lastSeenTicketComment->setTicket($event->getTicketComment()->getTicket());
                $lastSeenTicketComment->setUser($event->getUser());
                $lastSeenTicketComment->setLastSeenTicketComment($event->getTicketComment());

                $this->entityManager->persist($lastSeenTicketComment);
            } elseif (
                $lastSeenTicketComment->getLastSeenTicketComment() !== $event->getTicketComment()
            ) {
                $lastSeenTicketComment->setLastSeenTicketComment($event->getTicketComment());
            }
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
        $this->events->clear();
    }
}
