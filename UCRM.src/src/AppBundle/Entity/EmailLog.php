<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\Quote;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(indexes={@ORM\Index(columns={"created_date"})})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\EmailLogRepository")
 */
class EmailLog
{
    public const STATUS_OK = 0;
    public const STATUS_ERROR = 1;
    public const STATUS_RESENT = 2;

    public const STATUSES = [
        self::STATUS_OK,
        self::STATUS_ERROR,
        self::STATUS_RESENT,
    ];

    public const STATUS_REPLACE_STRING = [
        self::STATUS_OK => 'Sent',
        self::STATUS_ERROR => 'Error',
        self::STATUS_RESENT => 'Resent',
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="log_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="user_id", nullable=true)
     */
    protected $user;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime_utc")
     * @Assert\NotBlank()
     */
    protected $createdDate;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $message;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $script;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Choice(choices=EmailLog::STATUSES, strict=true)
     */
    protected $status;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     * @Assert\NotBlank()
     */
    protected $sender;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     * @Assert\NotBlank()
     */
    protected $recipient;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $originalRecipient;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $subject;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $body;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $attachments;

    /**
     * @var Client|null
     *
     * @ORM\ManyToOne(targetEntity="Client", inversedBy="emailLogs")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="client_id", nullable=true, onDelete="SET NULL")
     */
    protected $client;

    /**
     * @var Invoice|null
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Financial\Invoice", inversedBy="emailLogs")
     * @ORM\JoinColumn(name="invoice_id", referencedColumnName="invoice_id", nullable=true, onDelete="SET NULL")
     */
    protected $invoice;

    /**
     * @var Quote|null
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Financial\Quote", inversedBy="emailLogs")
     * @ORM\JoinColumn(name="quote_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $quote;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $failedRecipients;

    /**
     * @var string|null
     *
     * @ORM\Column(length=32, nullable=true, unique=true)
     * @Assert\Length(max = 32)
     */
    protected $messageId;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":false})
     */
    protected $sentInSandbox = false;

    /**
     * @var Mailing|null
     *
     * @ORM\ManyToOne(targetEntity="Mailing", inversedBy="emailLogs")
     * @ORM\JoinColumn(name="bulk_mail_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $bulkMail;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     * @Assert\NotBlank()
     */
    protected $addressFrom;

    /**
     * @var EmailLog|null
     *
     * @ORM\OneToOne(targetEntity="EmailLog")
     * @ORM\JoinColumn(referencedColumnName="log_id", nullable=true, onDelete="SET NULL")
     */
    protected $resentEmailLog;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":false})
     */
    protected $discarded = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getCreatedDate(): \DateTime
    {
        return $this->createdDate;
    }

    public function setCreatedDate(\DateTime $createdDate): void
    {
        $this->createdDate = $createdDate;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }

    public function getScript(): ?string
    {
        return $this->script;
    }

    public function setScript(?string $script): void
    {
        $this->script = $script;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): void
    {
        $this->status = $status;
    }

    public function getSender(): ?string
    {
        return $this->sender;
    }

    public function setSender(string $sender): void
    {
        $this->sender = $sender;
    }

    public function getRecipient(): ?string
    {
        return $this->recipient;
    }

    public function setRecipient(string $recipient): void
    {
        $this->recipient = $recipient;
    }

    public function getOriginalRecipient(): ?string
    {
        return $this->originalRecipient;
    }

    public function setOriginalRecipient(?string $originalRecipient): void
    {
        $this->originalRecipient = $originalRecipient;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    public function getAttachments(): ?string
    {
        return $this->attachments;
    }

    public function setAttachments(?string $attachments): void
    {
        $this->attachments = $attachments;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): void
    {
        $this->client = $client;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(?Invoice $invoice): void
    {
        $this->invoice = $invoice;
    }

    public function getQuote(): ?Quote
    {
        return $this->quote;
    }

    public function setQuote(?Quote $quote): void
    {
        $this->quote = $quote;
    }

    public function getFailedRecipients(): ?string
    {
        return $this->failedRecipients;
    }

    public function setFailedRecipients(?string $failedRecipients): void
    {
        $this->failedRecipients = $failedRecipients;
    }

    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    public function setMessageId(?string $messageId): void
    {
        $this->messageId = $messageId;
    }

    public function isSentInSandbox(): bool
    {
        return $this->sentInSandbox;
    }

    public function setSentInSandbox(bool $sentInSandbox): void
    {
        $this->sentInSandbox = $sentInSandbox;
    }

    public function getBulkMail(): ?Mailing
    {
        return $this->bulkMail;
    }

    public function setBulkMail(?Mailing $mail): void
    {
        $this->bulkMail = $mail;
    }

    public function getAddressFrom(): ?string
    {
        return $this->addressFrom;
    }

    public function setAddressFrom(string $addressFrom): void
    {
        $this->addressFrom = $addressFrom;
    }

    public function getResentEmailLog(): ?EmailLog
    {
        return $this->resentEmailLog;
    }

    public function setResentEmailLog(?EmailLog $resentEmailLog): void
    {
        $this->resentEmailLog = $resentEmailLog;
    }

    public function isDiscarded(): bool
    {
        return $this->discarded;
    }

    public function setDiscarded(bool $discarded): void
    {
        $this->discarded = $discarded;
    }
}
