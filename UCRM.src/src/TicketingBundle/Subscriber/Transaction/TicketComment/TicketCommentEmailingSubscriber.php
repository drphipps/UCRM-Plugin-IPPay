<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Subscriber\Transaction\TicketComment;

use AppBundle\Entity\EmailLog;
use AppBundle\Entity\Option;
use AppBundle\Exception\OptionNotFoundException;
use AppBundle\Exception\PublicUrlGeneratorException;
use AppBundle\Service\Email\EmailEnqueuer;
use AppBundle\Service\Email\EmailLogger;
use AppBundle\Service\ExceptionStash;
use AppBundle\Service\Options;
use AppBundle\Util\Message;
use Doctrine\ORM\EntityManagerInterface;
use Ds\Queue;
use TicketingBundle\Entity\TicketComment;
use TicketingBundle\Event\TicketComment\TicketCommentAddEvent;
use TicketingBundle\Service\Factory\NotificationEmailMessageFactory;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class TicketCommentEmailingSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var Queue|Message[]
     */
    private $messagesQueue;

    /**
     * @var Queue|TicketComment[]
     */
    private $newCommentsFromUserQueue;

    /**
     * @var Queue|TicketComment[]
     */
    private $newCommentsFromClientQueue;

    /**
     * @var EmailEnqueuer
     */
    private $emailEnqueuer;

    /**
     * @var EmailLogger
     */
    private $emailLogger;

    /**
     * @var NotificationEmailMessageFactory
     */
    private $notificationEmailMessageFactory;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var ExceptionStash
     */
    private $exceptionStash;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(
        EmailEnqueuer $emailEnqueuer,
        EmailLogger $emailLogger,
        NotificationEmailMessageFactory $notificationEmailMessageFactory,
        Options $options,
        ExceptionStash $exceptionStash,
        EntityManagerInterface $em
    ) {
        $this->emailEnqueuer = $emailEnqueuer;
        $this->emailLogger = $emailLogger;
        $this->notificationEmailMessageFactory = $notificationEmailMessageFactory;
        $this->options = $options;
        $this->exceptionStash = $exceptionStash;
        $this->em = $em;

        $this->messagesQueue = new Queue();
        $this->newCommentsFromUserQueue = new Queue();
        $this->newCommentsFromClientQueue = new Queue();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TicketCommentAddEvent::class => 'handleAddTicketCommentEvent',
        ];
    }

    public function handleAddTicketCommentEvent(TicketCommentAddEvent $event): void
    {
        $isUserTicketComment = (bool) $event->getTicketComment()->getUser();

        if (
            $isUserTicketComment
            && $this->options->get(Option::NOTIFICATION_TICKET_COMMENT_USER_CREATED_BY_EMAIL)
        ) {
            $ticketComment = $event->getTicketComment();

            if ($ticketComment->isPublic() && $ticketComment->getTicket()->isPublic()) {
                $this->newCommentsFromUserQueue->push($ticketComment);
            }
        }

        if (
            ! $isUserTicketComment
            && $this->options->get(Option::NOTIFICATION_TICKET_COMMENT_CLIENT_CREATED_BY_EMAIL)
        ) {
            $this->newCommentsFromClientQueue->push($event->getTicketComment());
        }
    }

    public function preFlush(): void
    {
    }

    public function preCommit(): void
    {
        foreach ($this->newCommentsFromUserQueue as $ticketComment) {
            if ($message = $this->createNewTicketCommentNotificationMessageForClient($ticketComment)) {
                $this->messagesQueue->push($message);
            }

            if ($message = $this->createNewTicketCommentNotificationMessageForSupport($ticketComment)) {
                $this->messagesQueue->push($message);
            }
        }

        foreach ($this->newCommentsFromClientQueue as $ticketComment) {
            if ($message = $this->createNewTicketCommentNotificationMessageForSupport($ticketComment)) {
                $this->messagesQueue->push($message);
            }
        }
    }

    public function postCommit(): void
    {
        foreach ($this->messagesQueue as $message) {
            $this->emailEnqueuer->enqueue(
                $message,
                EmailEnqueuer::PRIORITY_LOW
            );
        }

        $this->clearQueues();
    }

    public function rollback(): void
    {
        $this->clearQueues();
    }

    private function clearQueues(): void
    {
        $this->messagesQueue->clear();
        $this->newCommentsFromUserQueue->clear();
        $this->newCommentsFromClientQueue->clear();
    }

    private function updateEmailId(TicketComment $ticketComment, Message $message): void
    {
        $query = $this->em->createQuery(
            sprintf('update %s tc set tc.notificationEmailId = :emailId where tc.id = :id', TicketComment::class)
        );
        $query->execute(
            [
                'emailId' => $message->getId(),
                'id' => $ticketComment->getId(),
            ]
        );
        $ticketComment->setNotificationEmailId($message->getId());
    }

    private function createNewTicketCommentNotificationMessageForClient(TicketComment $ticketComment): ?Message
    {
        try {
            $message = $this->notificationEmailMessageFactory->createNewTicketCommentFromUserMessage($ticketComment);
        } catch (PublicUrlGeneratorException | OptionNotFoundException $exception) {
            $this->exceptionStash->add($exception);

            return null;
        }

        if (
            ! $ticketComment->getTicket()->getClient()
            && ! $ticketComment->getTicket()->getEmailFromAddress()
        ) {
            $this->emailLogger->log(
                $message,
                'Email could not be sent, because there is no client, nor origin email.',
                EmailLog::STATUS_ERROR
            );

            return null;
        }

        if (
            $ticketComment->getTicket()->getClient()
            && ! $ticketComment->getTicket()->getClient()->getContactEmails()
        ) {
            $this->emailLogger->log(
                $message,
                'Email could not be sent, because client has no email set.',
                EmailLog::STATUS_ERROR
            );

            return null;
        }

        return $message;
    }

    private function createNewTicketCommentNotificationMessageForSupport(TicketComment $ticketComment): ?Message
    {
        try {
            $message = $this->notificationEmailMessageFactory->createNotificationMessageForSupportNewTicketComment(
                $ticketComment
            );
        } catch (PublicUrlGeneratorException | OptionNotFoundException $exception) {
            $this->exceptionStash->add($exception);

            return null;
        }

        if (! $ticketComment->getEmailId()) {
            $this->updateEmailId($ticketComment, $message);
        }

        return $message;
    }
}
