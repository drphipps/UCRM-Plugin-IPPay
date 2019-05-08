<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Event\TicketComment;

use AppBundle\Entity\WebhookEvent;
use AppBundle\Interfaces\WebhookRequestableInterface;
use Symfony\Component\EventDispatcher\Event;
use TicketingBundle\Entity\TicketComment;

final class TicketCommentAddEvent extends Event implements WebhookRequestableInterface
{
    /**
     * @var TicketComment
     */
    private $ticketComment;

    /**
     * @var array
     */
    private $attachmentFiles;

    public function __construct(TicketComment $ticketComment, array $attachmentFiles = [])
    {
        $this->ticketComment = $ticketComment;
        $this->attachmentFiles = $attachmentFiles;
    }

    public function getTicketComment(): TicketComment
    {
        return $this->ticketComment;
    }

    public function getAttachmentFiles(): array
    {
        return $this->attachmentFiles;
    }

    public function getWebhookEntityClass(): string
    {
        return 'ticketComment';
    }

    /**
     * @return TicketComment
     */
    public function getWebhookEntity(): ?object
    {
        return $this->ticketComment;
    }

    public function getWebhookChangeType(): string
    {
        return WebhookEvent::COMMENT;
    }

    public function getWebhookEntityId(): ?int
    {
        return $this->ticketComment->getId();
    }

    public function getWebhookEntityBeforeEdit(): ?object
    {
        return null;
    }

    public function getEventName(): string
    {
        return 'ticket.comment';
    }
}
