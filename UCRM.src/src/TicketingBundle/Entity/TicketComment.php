<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use TicketingBundle\Interfaces\TicketActivityWithEmailInterface;

/**
 * @ORM\Entity(repositoryClass="TicketingBundle\Repository\TicketCommentRepository")
 */
class TicketComment extends TicketActivity implements TicketActivityWithEmailInterface
{
    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $body;

    /**
     * @var string|null
     *
     * @ORM\Column(length=255, nullable=true)
     * @Assert\Length(max = 255)
     * @Assert\Email(
     *     strict=true
     * )
     * @Assert\Expression(
     *     expression="not value or not this.getUser()",
     *     message="This field can be only set, when the comment is not created by admin."
     * )
     */
    private $emailFromAddress;

    /**
     * @var string|null
     *
     * @ORM\Column(length=255, nullable=true)
     * @Assert\Length(max = 255)
     * @Assert\Expression(
     *     expression="not value or not this.getUser()",
     *     message="This field can be only set, when the comment is not created by admin."
     * )
     */
    private $emailFromName;

    /**
     * IMAP's Message-ID without character "<" at beginning and ">" at end of string.
     *
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $emailId;

    /**
     * ID of email in IMAP mailbox (INBOX for us).
     *
     * @var int|null
     *
     * @deprecated Use date and ID of email instead ($this->emailDate and $this->emailID). This is abandoned since 2.12.0-beta1
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $imapUid;

    /**
     * @var Collection|TicketCommentAttachment[]
     *
     * @ORM\OneToMany(targetEntity="TicketingBundle\Entity\TicketCommentAttachment", mappedBy="ticketComment", cascade={"persist"}, orphanRemoval=true)
     */
    private $attachments;

    /**
     * @var Collection|TicketCommentMailAttachment[]
     *
     * @ORM\OneToMany(targetEntity="TicketCommentMailAttachment", mappedBy="ticketComment", cascade={"persist"}, orphanRemoval=true)
     */
    private $mailAttachments;

    /**
     * @var TicketImapInbox|null
     *
     * @ORM\ManyToOne(targetEntity="TicketingBundle\Entity\TicketImapInbox", inversedBy="ticketComments")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $inbox;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime_utc", nullable=true)
     */
    private $emailDate;

    /**
     * @var string|null
     *
     * @ORM\Column(length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    private $emailReplyToAddress;

    /**
     * @var string|null
     *
     * @ORM\Column(length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    private $emailReplyToName;

    /**
     * Email notification Message-ID without character "<" at beginning and ">" at end of string.
     *
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $notificationEmailId;

    public function __construct()
    {
        $this->attachments = new ArrayCollection();
        $this->mailAttachments = new ArrayCollection();

        parent::__construct();
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): void
    {
        $this->body = $body;
    }

    public function getEmailFromAddress(): ?string
    {
        return $this->emailFromAddress;
    }

    public function setEmailFromAddress(?string $emailFromAddress): void
    {
        $this->emailFromAddress = $emailFromAddress;
    }

    public function getEmailFromName(): ?string
    {
        return $this->emailFromName;
    }

    public function setEmailFromName(?string $emailFromName): void
    {
        $this->emailFromName = $emailFromName;
    }

    public function getEmailId(): ?string
    {
        return $this->emailId;
    }

    public function setEmailId(?string $emailId): void
    {
        $this->emailId = $emailId;
    }

    /**
     * @deprecated Use date and ID of email instead. This is abandoned since 2.12.0-beta1
     */
    public function getImapUid(): ?int
    {
        return $this->imapUid;
    }

    /**
     * @deprecated Use date and ID of email instead. This is abandoned since 2.12.0-beta1
     */
    public function setImapUid(?int $imapUid): void
    {
        $this->imapUid = $imapUid;
    }

    public function addAttachment(TicketCommentAttachment $ticketCommentAttachment): void
    {
        $ticketCommentAttachment->setTicketComment($this);
        $this->attachments[] = $ticketCommentAttachment;
    }

    /**
     * @return Collection|TicketCommentAttachment[]
     */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function getMailAttachments(): Collection
    {
        return $this->mailAttachments;
    }

    public function getInbox(): ?TicketImapInbox
    {
        return $this->inbox;
    }

    public function setInbox(?TicketImapInbox $inbox): void
    {
        $this->inbox = $inbox;
    }

    public function getEmailDate(): ?\DateTime
    {
        return $this->emailDate;
    }

    public function setEmailDate(?\DateTime $emailDate): void
    {
        $this->emailDate = $emailDate;
    }

    public function getEmailReplyToAddress(): ?string
    {
        return $this->emailReplyToAddress;
    }

    public function setEmailReplyToAddress(?string $emailReplyToAddress): void
    {
        $this->emailReplyToAddress = $emailReplyToAddress;
    }

    public function getEmailReplyToName(): ?string
    {
        return $this->emailReplyToName;
    }

    public function setEmailReplyToName(?string $emailReplyToName): void
    {
        $this->emailReplyToName = $emailReplyToName;
    }

    public function getNotificationEmailId(): ?string
    {
        return $this->notificationEmailId;
    }

    public function setNotificationEmailId(?string $notificationEmailId): void
    {
        $this->notificationEmailId = $notificationEmailId;
    }
}
