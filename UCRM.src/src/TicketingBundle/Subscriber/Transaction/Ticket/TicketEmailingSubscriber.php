<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Subscriber\Transaction\Ticket;

use AppBundle\Entity\EmailLog;
use AppBundle\Entity\Option;
use AppBundle\Exception\OptionNotFoundException;
use AppBundle\Exception\PublicUrlGeneratorException;
use AppBundle\Service\Email\EmailEnqueuer;
use AppBundle\Service\Email\EmailLogger;
use AppBundle\Service\ExceptionStash;
use AppBundle\Service\Options;
use AppBundle\Util\Message;
use Ds\Queue;
use TicketingBundle\Entity\Ticket;
use TicketingBundle\Entity\TicketComment;
use TicketingBundle\Entity\TicketStatusChange;
use TicketingBundle\Event\Ticket\TicketAddEvent;
use TicketingBundle\Event\Ticket\TicketAddImapEvent;
use TicketingBundle\Event\Ticket\TicketStatusChangedEvent;
use TicketingBundle\Service\Factory\NotificationEmailMessageFactory;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class TicketEmailingSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var Queue|Ticket[]
     */
    private $newTicketsFromClientQueue;

    /**
     * @var Queue|Ticket[]
     */
    private $newTicketsFromUserQueue;

    /**
     * @var Queue|Ticket[]
     */
    private $newAutomaticReplyTicketsQueue;

    /**
     * @var Queue|TicketStatusChange[]
     */
    private $newStatusChangesQueue;

    /**
     * @var Queue|Message[]
     */
    private $lowPriorityMessagesQueue;

    /**
     * @var Queue|Message[]
     */
    private $mediumPriorityMessagesQueue;

    /**
     * @var Queue|Message[]
     */
    private $highPriorityMessagesQueue;

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

    public function __construct(
        EmailEnqueuer $emailEnqueuer,
        EmailLogger $emailLogger,
        NotificationEmailMessageFactory $notificationEmailMessageFactory,
        Options $options,
        ExceptionStash $exceptionStash
    ) {
        $this->emailEnqueuer = $emailEnqueuer;
        $this->emailLogger = $emailLogger;
        $this->notificationEmailMessageFactory = $notificationEmailMessageFactory;
        $this->options = $options;
        $this->exceptionStash = $exceptionStash;

        $this->newStatusChangesQueue = new Queue();
        $this->newTicketsFromClientQueue = new Queue();
        $this->newTicketsFromUserQueue = new Queue();
        $this->highPriorityMessagesQueue = new Queue();
        $this->mediumPriorityMessagesQueue = new Queue();
        $this->lowPriorityMessagesQueue = new Queue();
        $this->newAutomaticReplyTicketsQueue = new Queue();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TicketAddImapEvent::class => 'handleAddTicketImapEvent',
            TicketAddEvent::class => 'handleAddTicketEvent',
            TicketStatusChangedEvent::class => 'handleTicketStatusChangedEvent',
        ];
    }

    public function handleAddTicketImapEvent(TicketAddImapEvent $event): void
    {
        if ($this->options->get(Option::TICKETING_IMAP_AUTOMATIC_REPLY_ENABLED)) {
            $this->newAutomaticReplyTicketsQueue->push($event->getTicket());
        }
    }

    public function handleAddTicketEvent(TicketAddEvent $event): void
    {
        $ticket = $event->getTicket();
        $isUserTicket = (bool) $ticket->getActivity()->current()->getUser();

        if ($isUserTicket && $this->options->get(Option::NOTIFICATION_TICKET_USER_CREATED_BY_EMAIL)) {
            $this->newTicketsFromUserQueue->push($ticket);
        }

        // As clients can't create private tickets, we don't want to send any messages for them.
        // Has to be here, because private ticket with only client and not user can be created via API.
        if (! $isUserTicket && $ticket->isPublic() && $this->options->get(
                Option::NOTIFICATION_TICKET_CLIENT_CREATED_BY_EMAIL
            )) {
            $this->newTicketsFromClientQueue->push($ticket);
        }
    }

    public function handleTicketStatusChangedEvent(TicketStatusChangedEvent $event): void
    {
        $ticket = $event->getTicketStatusChange()->getTicket();
        if ($this->options->get(Option::NOTIFICATION_TICKET_USER_CHANGED_STATUS) && $ticket->getClient()) {
            $this->newStatusChangesQueue->push($event->getTicketStatusChange());
        }
    }

    public function preFlush(): void
    {
        foreach ($this->newTicketsFromClientQueue as $ticket) {
            if ($message = $this->createNotificationMessageForSupport($ticket)) {
                $this->setEmptyNotificationEmailId($ticket, $message);
                $this->highPriorityMessagesQueue->push($message);
            }
        }

        foreach ($this->newTicketsFromUserQueue as $ticket) {
            if ($ticket->isPublic() && ($message = $this->createNewTicketNotificationMessageForClient($ticket))) {
                $this->setEmptyNotificationEmailId($ticket, $message);
                $this->lowPriorityMessagesQueue->push($message);
            }

            if ($message = $this->createNotificationMessageForSupport($ticket)) {
                $this->highPriorityMessagesQueue->push($message);
            }
        }

        foreach ($this->newAutomaticReplyTicketsQueue as $ticket) {
            try {
                $message = $this->notificationEmailMessageFactory->createAutomaticReplyMessageFromImapTicket($ticket);
            } catch (PublicUrlGeneratorException | OptionNotFoundException | \Swift_RfcComplianceException | \ErrorException  $exception) {
                $this->exceptionStash->add($exception);
                continue;
            }

            $this->mediumPriorityMessagesQueue->push($message);
        }

        foreach ($this->newStatusChangesQueue as $statusChange) {
            $ticket = $statusChange->getTicket();
            if ($ticket->isPublic() && $message = $this->createNotificationStatusChangedMessageForClient($ticket)) {
                if (! $statusChange->getEmailId()) {
                    $statusChange->setEmailId($message->getId());
                }
                $this->lowPriorityMessagesQueue->push($message);
            }
        }
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
        foreach ($this->highPriorityMessagesQueue as $message) {
            $this->emailEnqueuer->enqueue($message, EmailEnqueuer::PRIORITY_HIGH);
        }

        foreach ($this->mediumPriorityMessagesQueue as $message) {
            $this->emailEnqueuer->enqueue($message, EmailEnqueuer::PRIORITY_LOW);
        }

        foreach ($this->lowPriorityMessagesQueue as $message) {
            $this->emailEnqueuer->enqueue($message, EmailEnqueuer::PRIORITY_LOW);
        }

        $this->clearQueues();
    }

    public function rollback(): void
    {
        $this->clearQueues();
    }

    private function clearQueues(): void
    {
        $this->newTicketsFromClientQueue->clear();
        $this->newTicketsFromUserQueue->clear();
        $this->newAutomaticReplyTicketsQueue->clear();

        $this->newStatusChangesQueue->clear();
        $this->lowPriorityMessagesQueue->clear();
        $this->mediumPriorityMessagesQueue->clear();
        $this->highPriorityMessagesQueue->clear();
    }

    private function createNewTicketNotificationMessageForClient(Ticket $ticket): ?Message
    {
        try {
            $message = $this->notificationEmailMessageFactory->createNewTicketNotificationMessageForClient($ticket);
        } catch (PublicUrlGeneratorException $exception) {
            $this->exceptionStash->add($exception);

            return null;
        }

        if (! $ticket->getClient()->getContactEmails()) {
            $this->emailLogger->log(
                $message,
                'Email could not be sent, because client has no email set.',
                EmailLog::STATUS_ERROR
            );

            return null;
        }

        return $message;
    }

    private function createNotificationMessageForSupport(Ticket $ticket): ?Message
    {
        try {
            return $this->notificationEmailMessageFactory->createNotificationMessageForSupportNewTicket($ticket);
        } catch (PublicUrlGeneratorException | OptionNotFoundException $exception) {
            $this->exceptionStash->add($exception);

            return null;
        }
    }

    private function setEmptyNotificationEmailId(Ticket $ticket, Message $message): void
    {
        $ticketComment = $ticket->getComments()->first();
        assert($ticketComment instanceof TicketComment);
        if (! $ticketComment->getNotificationEmailId()) {
            $ticketComment->setNotificationEmailId($message->getId());
        }
    }

    private function createNotificationStatusChangedMessageForClient(Ticket $ticket)
    {
        try {
            $message = $this->notificationEmailMessageFactory->createStatusChangedMessage($ticket);
        } catch (PublicUrlGeneratorException $exception) {
            $this->exceptionStash->add($exception);

            return null;
        }

        if (! $ticket->getClient() || ! $ticket->getClient()->getContactEmails()) {
            $this->emailLogger->log(
                $message,
                'Email could not be sent, because client has no email set.',
                EmailLog::STATUS_ERROR
            );

            return null;
        }

        return $message;
    }
}
