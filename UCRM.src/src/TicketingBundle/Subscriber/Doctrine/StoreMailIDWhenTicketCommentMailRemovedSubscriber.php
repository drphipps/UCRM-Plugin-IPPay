<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Subscriber\Doctrine;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use EntitySubscribersBundle\Event\EntityEventSubscriber;
use TicketingBundle\Entity\TicketComment;
use TicketingBundle\Entity\TicketCommentMailRemoved;

class StoreMailIDWhenTicketCommentMailRemovedSubscriber implements EntityEventSubscriber
{
    public function subscribesToEntity(LoadClassMetadataEventArgs $event): bool
    {
        return TicketComment::class === $event->getClassMetadata()->getName();
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::preRemove,
        ];
    }

    public function preRemove(TicketComment $ticketComment, LifecycleEventArgs $eventArgs): void
    {
        if (! $ticketComment->getInbox() || ! $ticketComment->getEmailId()) {
            return;
        }

        if ($ticketComment->getInbox() && $ticketComment->getEmailId()) {
            $entityManager = $eventArgs->getEntityManager();

            $ticketCommentMailRemoved = new TicketCommentMailRemoved();
            $ticketCommentMailRemoved->setInbox($ticketComment->getInbox());
            $ticketCommentMailRemoved->setEmailId($ticketComment->getEmailId());

            $entityManager->persist($ticketCommentMailRemoved);
        }
    }
}
