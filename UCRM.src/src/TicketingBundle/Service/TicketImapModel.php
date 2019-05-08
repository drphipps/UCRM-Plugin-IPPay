<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Service;

use AppBundle\Exception\EmailAttachmentNotFoundException;
use AppBundle\Exception\EmailNotFoundException;
use AppBundle\Exception\ImapConnectionException;
use Ddeboer\Imap\ConnectionInterface;
use Ddeboer\Imap\Exception\ImapGetmailboxesException;
use Ddeboer\Imap\Exception\InvalidResourceException;
use Ddeboer\Imap\Exception\MailboxDoesNotExistException;
use Ddeboer\Imap\Exception\MessageDoesNotExistException;
use Ddeboer\Imap\Exception\ReopenMailboxException;
use Ddeboer\Imap\MailboxInterface;
use Ddeboer\Imap\MessageInterface;
use Ddeboer\Imap\MessageIteratorInterface;
use Ddeboer\Imap\Search\Date\On;
use Ddeboer\Imap\Search\Date\Since;
use TicketingBundle\Entity\TicketComment;
use TicketingBundle\Entity\TicketCommentAttachmentInterface;

class TicketImapModel
{
    /**
     * @var ConnectionInterface
     */
    private $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;

        imap_timeout(IMAP_OPENTIMEOUT, 1);
    }

    /**
     * @throws ImapConnectionException
     */
    public function checkConnection(): bool
    {
        try {
            return $this->connection->ping();
        } catch (\RuntimeException $exception) {
            throw new ImapConnectionException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception->getPrevious()
            );
        }
    }

    /**
     * @throws EmailNotFoundException
     * @throws EmailAttachmentNotFoundException
     * @throws ImapConnectionException
     * @throws MailboxDoesNotExistException
     */
    public function getAttachmentContent(TicketCommentAttachmentInterface $ticketCommentAttachment): string
    {
        foreach (
            $this->getMailByTicketComment($ticketCommentAttachment->getTicketComment())->getAttachments()
            as $attachment
        ) {
            if ($ticketCommentAttachment->getPartId() === $attachment->getPartNumber()) {
                return $attachment->getDecodedContent();
            }
        }

        throw new EmailAttachmentNotFoundException('Attachment not found.');
    }

    /**
     * @throws EmailNotFoundException
     * @throws ImapConnectionException
     * @throws MessageDoesNotExistException
     * @throws ImapGetmailboxesException
     */
    public function getMailByTicketComment(TicketComment $ticketComment): MessageInterface
    {
        if ($ticketComment->getEmailDate()) {
            foreach ($this->getInbox()->getMessages(new On($ticketComment->getEmailDate())) as $message) {
                if ($ticketComment->getEmailId() === trim($message->getId() ?? '', '<>')) {
                    return $message;
                }
            }
            // Fallback for pre 2.12.0-beta1 versions
        } elseif ($ticketComment->getImapUid()) {
            $message = $this->getInbox()->getMessage($ticketComment->getImapUid());
            $message->getContent(); // it is also check of availability in IMAP inbox
            return $message;
        }

        throw new EmailNotFoundException(
            sprintf(
                'Message from %s is not found in IMAP server ',
                $ticketComment->getEmailFromAddress()
            )
        );
    }

    /**
     * @throws ImapConnectionException
     * @throws InvalidResourceException
     * @throws MailboxDoesNotExistException
     * @throws ReopenMailboxException
     * @throws ImapGetmailboxesException
     */
    public function getMessagesSince(\DateTimeImmutable $date): MessageIteratorInterface
    {
        return $this->getInbox()->getMessages(
            new Since($date),
            \SORTARRIVAL
        );
    }

    /**
     * @throws InvalidResourceException
     * @throws MailboxDoesNotExistException
     * @throws ReopenMailboxException
     * @throws ImapGetmailboxesException
     */
    private function getInbox(): MailboxInterface
    {
        return $this->connection->getMailbox('INBOX');
    }
}
