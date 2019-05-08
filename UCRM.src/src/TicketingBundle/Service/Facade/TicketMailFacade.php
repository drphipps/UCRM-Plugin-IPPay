<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Service\Facade;

use AppBundle\Entity\ClientContact;
use AppBundle\Entity\Option;
use AppBundle\Service\Options;
use AppBundle\Util\DateTimeFactory;
use AppBundle\Util\ImapMessageParser;
use AppBundle\Util\UnitConverter\BinaryConverter;
use Ddeboer\Imap\MessageInterface;
use Doctrine\ORM\EntityManagerInterface;
use EmailReplyParser\Parser\EmailParser;
use Nette\Utils\Strings;
use TicketingBundle\Entity\Ticket;
use TicketingBundle\Entity\TicketComment;
use TicketingBundle\Entity\TicketCommentAttachmentInterface;
use TicketingBundle\Entity\TicketImapInbox;
use TicketingBundle\Event\Ticket\TicketAddEvent;
use TicketingBundle\Event\Ticket\TicketAddImapEvent;
use TicketingBundle\Event\Ticket\TicketEditEvent;
use TicketingBundle\Event\TicketComment\TicketCommentAddEvent;
use TicketingBundle\Service\Factory\CommentFactory;
use TicketingBundle\Service\Factory\CommentMailAttachmentFactory;
use TicketingBundle\Service\Factory\TicketImapModelFactory;
use TicketingBundle\Service\TicketFinder;
use TransactionEventsBundle\TransactionDispatcher;

class TicketMailFacade
{
    /**
     * @var CommentFactory
     */
    private $commentFactory;

    /**
     * @var CommentMailAttachmentFactory
     */
    private $commentMailAtachmentFactory;

    /**
     * @var EmailParser
     */
    private $emailParser;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var TicketFinder
     */
    private $ticketFinder;

    /**
     * @var TicketImapModelFactory
     */
    private $ticketImapModelFactory;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    public function __construct(
        CommentFactory $commentFactory,
        CommentMailAttachmentFactory $commentMailAtachmentFactory,
        EmailParser $emailParser,
        Options $options,
        TicketFinder $ticketFinder,
        TicketImapModelFactory $ticketImapModelFactory,
        TransactionDispatcher $transactionDispatcher
    ) {
        $this->commentFactory = $commentFactory;
        $this->commentMailAtachmentFactory = $commentMailAtachmentFactory;
        $this->emailParser = $emailParser;
        $this->options = $options;
        $this->ticketFinder = $ticketFinder;
        $this->ticketImapModelFactory = $ticketImapModelFactory;
        $this->transactionDispatcher = $transactionDispatcher;
    }

    /**
     * Creates new ticket comment or new ticket.
     *
     * @throws \Throwable
     */
    public function handleNewEmail(MessageInterface $message, TicketImapInbox $ticketImapInbox): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $em) use ($message, $ticketImapInbox) {
                $ticket = $this->ticketFinder->findByNewIncomingEmail($message);
                if ($ticket) {
                    $ticketComment = $this->addFoundTicket($ticket, $message, $ticketImapInbox);
                    $em->persist($ticketComment);

                    if ($ticket->getStatus() === Ticket::STATUS_SOLVED) {
                        $ticketBeforeUpdate = clone $ticket;
                        $ticket->setStatus(Ticket::STATUS_OPEN);
                        yield new TicketEditEvent($ticket, $ticketBeforeUpdate);
                    }

                    yield new TicketCommentAddEvent($ticketComment, $message->getAttachments());
                } else {
                    $ticket = $this->addNotFoundTicket($message, $em, $ticketImapInbox);
                    if ($group = $ticketImapInbox->getTicketGroup()) {
                        $ticket->setGroup($group);
                    }
                    $em->persist($ticket);
                    yield new TicketAddImapEvent($ticket);
                    yield new TicketAddEvent(
                        $ticket,
                        $ticket->getComments()->first(),
                        $message->getAttachments()
                    );
                }
            }
        );
    }

    /**
     * @throws \RuntimeException
     */
    public function getAttachment(TicketCommentAttachmentInterface $ticketCommentAttachment): string
    {
        if (! $inbox = $ticketCommentAttachment->getTicketComment()->getInbox()) {
            throw new \RuntimeException('IMAP inbox for this comment is not available');
        }

        return $this->ticketImapModelFactory->create($inbox)->getAttachmentContent($ticketCommentAttachment);
    }

    private function addFoundTicket(
        Ticket $ticket,
        MessageInterface $message,
        TicketImapInbox $ticketImapInbox
    ): TicketComment {
        $ticketComment = $this->createComment($ticket, $message, $ticketImapInbox);

        $this->addAttachments($ticketComment, $message);

        return $ticketComment;
    }

    private function addNotFoundTicket(
        MessageInterface $message,
        EntityManagerInterface $em,
        TicketImapInbox $ticketImapInbox
    ): Ticket {
        $ticket = new Ticket();

        $udate = $message->getHeaders()->get('udate')
            ? new \DateTime(sprintf('@%s', $message->getHeaders()->get('udate')))
            : new \DateTime();

        $ticket->setCreatedAt($udate);
        $ticket->setSubject(Strings::substring($message->getSubject() ?? '', 0, 255));

        $ticketComment = $this->createComment($ticket, $message, $ticketImapInbox);
        $this->addAttachments($ticketComment, $message);
        $ticket->addActivity($ticketComment);

        $clientRepository = $em->getRepository(ClientContact::class);

        $emails = [];
        foreach ($message->getReplyTo() as $reply) {
            $emails[] = $reply->getAddress();
        }

        $emails[] = $message->getFrom()->getAddress();
        foreach (array_unique($emails) as $email) {
            if ($client = $clientRepository->findExactlyOneClientByContactEmail($email)) {
                $ticket->setClient($client);
                break;
            }
        }

        if ($replyToArray = $message->getReplyTo()) {
            $replyTo = current($replyToArray);
            $emailFromAddress = $replyTo->getAddress();
            $emailFromName = $replyTo->getName();
        } else {
            $emailFromAddress = $message->getFrom()->getAddress();
            $emailFromName = $message->getFrom()->getName();
        }

        $ticket->setEmailFromAddress($emailFromAddress);

        if (! $ticket->getClient()) {
            $ticket->setEmailFromName($emailFromName);
        }

        return $ticket;
    }

    private function createComment(
        Ticket $ticket,
        MessageInterface $message,
        TicketImapInbox $ticketImapInbox
    ): TicketComment {
        $ticketComment = $this->commentFactory->create($ticket);

        $emailBody = ImapMessageParser::getBodyText($message) ?: strip_tags(ImapMessageParser::getBodyHtml($message));
        if ($emailBody) {
            $ticketComment->setBody($this->emailParser->parse($emailBody)->getVisibleText());
        }

        $ticketComment->setEmailId(trim($message->getId(), '<>'));
        $ticketComment->setEmailDate(DateTimeFactory::createFromInterface($message->getDate()));

        $udate = $message->getHeaders()->get('udate')
            ? new \DateTime(sprintf('@%s', $message->getHeaders()->get('udate')))
            : new \DateTime();

        $ticketComment->setCreatedAt($udate);

        $from = $message->getFrom();
        $ticketComment->setEmailFromAddress($from->getAddress());
        $ticketComment->setEmailFromName($from->getName());
        $ticketComment->setInbox($ticketImapInbox);

        if ($replyToArray = $message->getReplyTo()) {
            $replyTo = current($replyToArray);
            $ticketComment->setEmailReplyToAddress(
                $replyTo->getAddress() === $from->getAddress() ? null : $replyTo->getAddress()
            );
            $ticketComment->setEmailReplyToName(
                $replyTo->getAddress() === $from->getAddress() ? null : $replyTo->getName()
            );
        }

        return $ticketComment;
    }

    private function addAttachments(TicketComment $ticketComment, MessageInterface $message): void
    {
        $limit = new BinaryConverter(
            $this->options->get(Option::TICKETING_IMAP_ATTACHMENT_FILESIZE_IMPORT_LIMIT),
            BinaryConverter::UNIT_MEBI
        );

        foreach ($message->getAttachments() as $attachment) {
            if ($limit->to(BinaryConverter::UNIT_BYTE) < $attachment->getBytes()) {
                $ticketCommentMailAttachment = $this->commentMailAtachmentFactory->createFromMessageAttachment(
                    $attachment
                );
                $ticketCommentMailAttachment->setTicketComment($ticketComment);
                $ticketComment->getMailAttachments()->add($ticketCommentMailAttachment);
            }
        }
    }
}
