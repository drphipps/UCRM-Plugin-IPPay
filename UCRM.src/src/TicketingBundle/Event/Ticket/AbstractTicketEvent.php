<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Event\Ticket;

use AppBundle\Interfaces\WebhookRequestableInterface;
use Symfony\Component\EventDispatcher\Event;
use TicketingBundle\Entity\Ticket;
use TicketingBundle\Entity\TicketComment;

abstract class AbstractTicketEvent extends Event implements WebhookRequestableInterface
{
    /**
     * @var Ticket
     */
    protected $ticket;

    /**
     * @var array
     */
    protected $attachmentFiles;

    /**
     * @var TicketComment|null
     */
    protected $ticketComment;

    public function __construct(Ticket $ticket, ?TicketComment $ticketComment = null, array $attachmentFiles = [])
    {
        $this->ticket = $ticket;
        $this->ticketComment = $ticketComment;
        $this->attachmentFiles = $attachmentFiles;
    }

    public function getTicket(): Ticket
    {
        return $this->ticket;
    }

    public function getTicketComment(): ?TicketComment
    {
        return $this->ticketComment;
    }

    public function getAttachmentFiles(): array
    {
        return $this->attachmentFiles;
    }

    public function getWebhookEntityClass(): string
    {
        return 'ticket';
    }

    /**
     * @return Ticket
     */
    public function getWebhookEntity(): ?object
    {
        return $this->ticket;
    }

    /**
     * @return Ticket|null
     */
    public function getWebhookEntityBeforeEdit(): ?object
    {
        return null;
    }

    public function getWebhookEntityId(): ?int
    {
        return $this->ticket->getId();
    }
}
