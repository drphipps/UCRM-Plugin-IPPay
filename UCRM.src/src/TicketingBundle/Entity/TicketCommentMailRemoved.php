<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class TicketCommentMailRemoved
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var TicketImapInbox
     *
     * @ORM\ManyToOne(targetEntity="TicketingBundle\Entity\TicketImapInbox")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $inbox;

    /**
     * IMAP's Message-ID without character "<" at beginning and ">" at end of string.
     *
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $emailId;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getInbox(): TicketImapInbox
    {
        return $this->inbox;
    }

    public function setInbox(TicketImapInbox $inbox): void
    {
        $this->inbox = $inbox;
    }

    public function getEmailId(): string
    {
        return $this->emailId;
    }

    public function setEmailId(string $emailId): void
    {
        $this->emailId = $emailId;
    }
}
