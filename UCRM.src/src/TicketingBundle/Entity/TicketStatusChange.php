<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use TicketingBundle\Interfaces\TicketActivityWithEmailInterface;

/**
 * @ORM\Entity()
 */
class TicketStatusChange extends TicketActivity implements TicketActivityWithEmailInterface
{
    /**
     * IMAP's Message-ID without character "<" at beginning and ">" at end of string.
     *
     * Used to map email back to ticket, when client is replying to "ticket status changed notification".
     * Otherwise the email reply would create new ticket.
     *
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $emailId;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint", options={"unsigned": true})
     * @Assert\Choice(choices="Ticket::STATUSES_NUMERIC", strict=true)
     */
    private $status;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint", options={"unsigned": true}, nullable=true)
     * @Assert\Choice(choices="Ticket::STATUSES_NUMERIC", strict=true)
     */
    private $previousStatus;

    public function getEmailId(): ?string
    {
        return $this->emailId;
    }

    public function setEmailId(?string $emailId): void
    {
        $this->emailId = $emailId;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    public function getPreviousStatus(): ?int
    {
        return $this->previousStatus;
    }

    public function setPreviousStatus(int $status): void
    {
        $this->previousStatus = $status;
    }
}
