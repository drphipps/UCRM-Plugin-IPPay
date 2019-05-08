<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Service\Facade;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use TicketingBundle\Entity\Ticket;
use TicketingBundle\Entity\TicketComment;
use TicketingBundle\Entity\TicketCommentAttachment;
use TicketingBundle\Entity\TicketCommentMailAttachment;
use TicketingBundle\Event\Ticket\TicketEditEvent;
use TicketingBundle\Event\TicketComment\TicketCommentAddEvent;
use TicketingBundle\Form\Data\TicketCommentClientData;
use TicketingBundle\Form\Data\TicketCommentUserData;
use TicketingBundle\Service\Factory\CommentFactory;
use TransactionEventsBundle\TransactionDispatcher;

class TicketCommentFacade
{
    /**
     * @var CommentFactory
     */
    private $commentFactory;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    public function __construct(
        CommentFactory $commentFactory,
        TransactionDispatcher $transactionDispatcher
    ) {
        $this->commentFactory = $commentFactory;
        $this->transactionDispatcher = $transactionDispatcher;
    }

    public function handleNewFromAPI(TicketComment $ticketComment, array $attachmentMap): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($ticketComment, $attachmentMap) {
                $entityManager->persist($ticketComment);

                yield new TicketCommentAddEvent($ticketComment, $attachmentMap);
            }
        );
    }

    public function handleNewFromData(TicketCommentUserData $ticketCommentUserData, User $user): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($ticketCommentUserData, $user) {
                $ticketComment = $this->commentFactory->create($ticketCommentUserData->ticket);
                $ticketComment->setBody($ticketCommentUserData->body);
                $ticketComment->setUser($user);
                $ticketComment->setPublic(! $ticketCommentUserData->private);

                $entityManager->persist($ticketComment);

                yield new TicketCommentAddEvent($ticketComment, $ticketCommentUserData->attachmentFiles);
            }
        );
    }

    public function handleNewFromClientData(TicketCommentClientData $ticketCommentClientData): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($ticketCommentClientData) {
                $ticket = $ticketCommentClientData->ticket;
                $ticketComment = $this->commentFactory->create($ticket);
                $ticketComment->setBody($ticketCommentClientData->body);

                if ($ticket->getStatus() === Ticket::STATUS_SOLVED) {
                    $ticketBeforeUpdate = clone $ticket;
                    $ticket->setStatus(Ticket::STATUS_OPEN);
                    yield new TicketEditEvent($ticket, $ticketBeforeUpdate);
                }

                $entityManager->persist($ticketComment);

                yield new TicketCommentAddEvent($ticketComment, $ticketCommentClientData->attachmentFiles);
            }
        );
    }

    public function handleDeleteAttachment(TicketCommentAttachment $ticketCommentAttachment): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($ticketCommentAttachment) {
                if ($ticketCommentAttachment->getPartId()) {
                    $ticketCommentMailAttachment = new TicketCommentMailAttachment();
                    $ticketCommentMailAttachment->setFilename($ticketCommentAttachment->getFilename());
                    $ticketCommentMailAttachment->setMimeType($ticketCommentAttachment->getMimeType());
                    $ticketCommentMailAttachment->setPartId($ticketCommentAttachment->getPartId());
                    $ticketCommentMailAttachment->setSize($ticketCommentAttachment->getSize());
                    $ticketCommentMailAttachment->setTicketComment($ticketCommentAttachment->getTicketComment());

                    $entityManager->persist($ticketCommentMailAttachment);
                }

                $entityManager->remove($ticketCommentAttachment);
            }
        );
    }
}
