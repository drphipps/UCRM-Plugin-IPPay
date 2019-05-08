<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Financial;

use AppBundle\Entity\Client;
use AppBundle\Entity\EmailLog;
use AppBundle\Entity\InvoiceAttribute;
use AppBundle\Entity\LoggableInterface;
use AppBundle\Entity\ParentLoggableInterface;
use AppBundle\Entity\PaymentCover;
use AppBundle\Entity\PaymentToken;
use AppBundle\Entity\Site;
use AppBundle\Util\Financial\FinancialItemSorter;
use ArrayIterator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(
 *      name="invoice",
 *      indexes={
 *          @ORM\Index(name="invoice_created_date_idx", columns={"created_date"}),
 *          @ORM\Index(name="invoice_due_date_idx", columns={"due_date"}),
 *          @ORM\Index(name="invoice_status_idx", columns={"invoice_status"}),
 *          @ORM\Index(name="invoice_email_sent_date_idx", columns={"email_sent_date"}),
 *      },
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="invoice_number_organization_proforma_id_key",
 *              columns={"invoice_number", "organization_id", "is_proforma"}
 *          )
 *     })
 * @ORM\Entity(repositoryClass="AppBundle\Repository\InvoiceRepository")
 *
 * @UniqueEntity({"invoiceNumber", "organization", "isProforma"}, message="This invoice number is already used.")
 *
 * @Assert\Expression(
 *     "this.getInvoiceTemplate() || this.getProformaInvoiceTemplate()",
 *     message="Template must be set"
 * )
 */
class Invoice implements LoggableInterface, ParentLoggableInterface, FinancialInterface
{
    use FinancialTrait;

    // invoice statuses
    public const DRAFT = 0;
    public const UNPAID = 1;
    public const PARTIAL = 2;
    public const PAID = 3;
    public const VOID = 4;
    public const PROFORMA_PROCESSED = 5;

    public const STATUSES = [
        self::DRAFT,
        self::UNPAID,
        self::PARTIAL,
        self::PAID,
        self::VOID,
        self::PROFORMA_PROCESSED,
    ];

    // translation int status to string
    public const STATUS_REPLACE_STRING = [
        self::DRAFT => 'Draft',
        self::UNPAID => 'Unpaid',
        self::PARTIAL => 'Partial',
        self::PAID => 'Paid',
        self::VOID => 'invoice_status_void',
        self::PROFORMA_PROCESSED => 'Processed',
    ];

    public const UNPAID_STATUSES = [
        self::UNPAID,
        self::PARTIAL,
    ];

    public const PAID_STATUSES = [
        self::PAID,
        self::PROFORMA_PROCESSED,
    ];

    public const VALID_STATUSES = [
        self::UNPAID,
        self::PARTIAL,
        self::PAID,
        self::PROFORMA_PROCESSED,
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="invoice_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var Client
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Client", inversedBy="invoices")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="client_id", nullable=false, onDelete="CASCADE")
     * @Assert\NotNull()
     */
    protected $client;

    /**
     * @var string|null
     *
     * @ORM\Column(name="invoice_number", type="string", length=60, nullable=true)
     * @Assert\Length(max = 60)
     */
    protected $invoiceNumber;

    /**
     * @var int|null
     *
     * @ORM\Column(name="invoice_status", type="integer", nullable=true)
     */
    protected $invoiceStatus;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="due_date", type="datetime_utc")
     */
    protected $dueDate;

    /**
     * @var Collection|InvoiceItem[]
     *
     * @ORM\OneToMany(targetEntity="InvoiceItem", mappedBy="invoice", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="invoice_id", referencedColumnName="invoice_id")
     * @ORM\OrderBy({"id" = "ASC"})
     *
     * @Assert\Count(min=1, minMessage="Invoice must have at least one item.", groups={FinancialInterface::VALIDATION_GROUP_API})
     * @Assert\Valid()
     */
    protected $invoiceItems;

    /**
     * @var Collection|PaymentCover[]
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\PaymentCover", mappedBy="invoice", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="invoice_id", referencedColumnName="invoice_id")
     */
    protected $paymentCovers;

    /**
     * @var bool
     *
     * @ORM\Column(name="overdue_notification_sent", type="boolean", options={"default":false})
     */
    protected $overdueNotificationSent = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="near_due_notification_sent", type="boolean", options={"default":false})
     */
    protected $nearDueNotificationSent = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="pdf_batch_printed", type="boolean", options={"default":false})
     */
    protected $pdfBatchPrinted = false;

    /**
     * @var Collection|EmailLog[]
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\EmailLog", mappedBy="invoice")
     * @ORM\JoinColumn(name="invoice_id", referencedColumnName="invoice_id")
     */
    protected $emailLogs;

    /**
     * @var InvoiceTemplate|null
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Financial\InvoiceTemplate")
     */
    protected $invoiceTemplate;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":false})
     * @Assert\NotNull()
     */
    protected $lateFeeCreated = false;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":true})
     */
    protected $canCauseSuspension = true;

    /**
     * @var int|null
     *
     * @ORM\Column(name="invoice_maturity_days", type="integer", nullable=true)
     * @Assert\LessThanOrEqual(value = 36500)
     * @Assert\GreaterThanOrEqual(value = 0)
     */
    protected $invoiceMaturityDays;

    /**
     * @var float
     *
     * @ORM\Column(name="amount_paid", type="float", options={"default":0})
     */
    protected $amountPaid = 0;

    /**
     * @var Collection|InvoiceAttribute[]
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\InvoiceAttribute", mappedBy="invoice", cascade={"persist"}, orphanRemoval=true)
     * @Assert\Valid()
     */
    protected $attributes;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":false})
     */
    protected $uncollectible = false;

    /**
     * @var PaymentToken|null
     *
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\PaymentToken", mappedBy="invoice", cascade={"remove"})
     */
    protected $paymentToken;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": false})
     */
    protected $isProforma = false;

    /**
     * Parent proforma invoice.
     *
     * @var Invoice|null
     *
     * @ORM\OneToOne(targetEntity="Invoice")
     * @ORM\JoinColumn(referencedColumnName="invoice_id", onDelete="SET NULL")
     */
    protected $proformaInvoice;

    /**
     * Invoice generated by proforma invoice.
     *
     * @var Invoice|null
     *
     * @ORM\OneToOne(targetEntity="Invoice")
     * @ORM\JoinColumn(referencedColumnName="invoice_id", onDelete="SET NULL")
     */
    protected $generatedInvoice;

    /**
     * @var ProformaInvoiceTemplate|null
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Financial\ProformaInvoiceTemplate")
     */
    protected $proformaInvoiceTemplate;

    /**
     * @var \DateTimeImmutable|null
     *
     * @ORM\Column(type="datetime_immutable_utc", nullable=true)
     */
    protected $taxableSupplyDate;

    public function __construct()
    {
        $this->invoiceItems = new ArrayCollection();
        $this->paymentCovers = new ArrayCollection();
        $this->emailLogs = new ArrayCollection();
        $this->attributes = new ArrayCollection();
    }

    public function setInvoiceNumber(?string $invoiceNumber): void
    {
        $this->invoiceNumber = $invoiceNumber;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceStatus(?int $invoiceStatus): void
    {
        $this->invoiceStatus = $invoiceStatus;
    }

    public function getInvoiceStatus(): ?int
    {
        return $this->invoiceStatus;
    }

    public function addInvoiceItem(InvoiceItem $invoiceItem): void
    {
        $this->invoiceItems[] = $invoiceItem;
    }

    public function removeInvoiceItem(InvoiceItem $invoiceItem): void
    {
        $this->invoiceItems->removeElement($invoiceItem);
    }

    /**
     * @return Collection|InvoiceItem[]
     */
    public function getInvoiceItems(): Collection
    {
        return $this->invoiceItems;
    }

    public function addItem(FinancialItemInterface $item): void
    {
        if (! $item instanceof InvoiceItem) {
            throw new \InvalidArgumentException('Item not supported.');
        }

        $this->addInvoiceItem($item);
    }

    public function removeItem(FinancialItemInterface $item): void
    {
        if (! $item instanceof InvoiceItem) {
            throw new \InvalidArgumentException('Item not supported.');
        }

        $this->removeInvoiceItem($item);
    }

    /**
     * @return Collection|FinancialItemInterface[]
     */
    public function getItems(): Collection
    {
        return $this->getInvoiceItems();
    }

    /**
     * @return Collection|FinancialItemInterface[]
     */
    public function getItemsSorted(): Collection
    {
        return FinancialItemSorter::sort($this->invoiceItems);
    }

    public function addPaymentCover(PaymentCover $paymentCover): void
    {
        $this->paymentCovers[] = $paymentCover;
    }

    public function removePaymentCover(PaymentCover $paymentCover): void
    {
        $this->paymentCovers->removeElement($paymentCover);
    }

    /**
     * @return Collection|PaymentCover[]
     */
    public function getPaymentCovers(): Collection
    {
        return $this->paymentCovers;
    }

    public function setDueDate(\DateTime $dueDate): void
    {
        $this->dueDate = $dueDate;
    }

    public function getDueDate(): ?\DateTime
    {
        return $this->dueDate;
    }

    public function getPdfBatchPrinted(): bool
    {
        return $this->pdfBatchPrinted;
    }

    public function setPdfBatchPrinted(bool $pdfBatchPrinted): void
    {
        $this->pdfBatchPrinted = $pdfBatchPrinted;
    }

    public function getOverdueNotificationSent(): bool
    {
        return $this->overdueNotificationSent;
    }

    public function setOverdueNotificationSent(bool $overdueNotificationSent): void
    {
        $this->overdueNotificationSent = $overdueNotificationSent;
    }

    public function getNearDueNotificationSent(): bool
    {
        return $this->nearDueNotificationSent;
    }

    public function setNearDueNotificationSent(bool $nearDueNotificationSent): void
    {
        $this->nearDueNotificationSent = $nearDueNotificationSent;
    }

    public function addEmailLog(EmailLog $emailLog): void
    {
        $this->emailLogs[] = $emailLog;
    }

    public function removeEmailLog(EmailLog $emailLog): void
    {
        $this->emailLogs->removeElement($emailLog);
    }

    /**
     * @return Collection|EmailLog[]
     */
    public function getEmailLogs(): Collection
    {
        return $this->emailLogs;
    }

    public function getInvoiceTemplate(): ?InvoiceTemplate
    {
        return $this->invoiceTemplate;
    }

    public function setInvoiceTemplate(?InvoiceTemplate $invoiceTemplate): void
    {
        $this->invoiceTemplate = $invoiceTemplate;
    }

    public function isLateFeeCreated(): bool
    {
        return $this->lateFeeCreated;
    }

    public function setLateFeeCreated(bool $lateFeeCreated): void
    {
        $this->lateFeeCreated = $lateFeeCreated;
    }

    public function isCanCauseSuspension(): bool
    {
        return $this->canCauseSuspension;
    }

    public function setCanCauseSuspension(bool $canCauseSuspension): void
    {
        $this->canCauseSuspension = $canCauseSuspension;
    }

    public function setInvoiceMaturityDays(?int $invoiceMaturityDays): void
    {
        $this->invoiceMaturityDays = $invoiceMaturityDays;
    }

    public function getInvoiceMaturityDays(): ?int
    {
        return $this->invoiceMaturityDays;
    }

    public function setAmountPaid(float $amountPaid): void
    {
        $this->amountPaid = $amountPaid;
    }

    public function getAmountPaid(): float
    {
        return $this->amountPaid;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDeleteMessage(): array
    {
        if (trim((string) $this->getInvoiceNumber()) === '') {
            $message['logMsg'] = [
                'message' => 'Invoice draft deleted',
                'replacements' => '',
            ];
        } else {
            $message['logMsg'] = [
                'message' => 'Invoice %s deleted',
                'replacements' => $this->getInvoiceNumber(),
            ];
        }

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogInsertMessage(): array
    {
        if (trim((string) $this->getInvoiceNumber()) === '') {
            $message['logMsg'] = [
                'message' => 'Invoice draft added',
                'replacements' => '',
            ];
        } else {
            $message['logMsg'] = [
                'message' => 'Invoice %s added',
                'replacements' => $this->getInvoiceNumber(),
            ];
        }

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogIgnoredColumns(): array
    {
        return [
            'amountPaid',
            'emailSentDate',
            'pdfPath',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getLogClient(): ?Client
    {
        return $this->getClient();
    }

    /**
     * {@inheritdoc}
     */
    public function getLogSite(): ?Site
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogParentEntity(): ?Client
    {
        return $this->getClient();
    }

    /**
     * {@inheritdoc}
     */
    public function getLogUpdateMessage(): array
    {
        $message['logMsg'] = [
            'id' => $this->getId(),
            'message' => $this->getInvoiceNumber(),
            'entity' => self::class,
        ];

        return $message;
    }

    public function isOverdue(): bool
    {
        $now = new \DateTime('today midnight');

        return in_array($this->invoiceStatus, self::UNPAID_STATUSES, true) && $now > $this->dueDate;
    }

    public function getAmountToPay(): float
    {
        return max($this->getTotal() - $this->getAmountPaid(), 0.0);
    }

    /**
     * Returns invoice status as human readable text.
     */
    public function getInvoiceStatusName(): string
    {
        return self::STATUS_REPLACE_STRING[$this->invoiceStatus];
    }

    public function addAttribute(InvoiceAttribute $attribute): void
    {
        $attribute->setInvoice($this);
        $this->attributes->add($attribute);
    }

    public function removeAttribute(InvoiceAttribute $attribute): void
    {
        $this->attributes->removeElement($attribute);
    }

    /**
     * @return Collection|InvoiceAttribute[]
     */
    public function getAttributes(): Collection
    {
        return $this->attributes;
    }

    /**
     * @return \Iterator|InvoiceAttribute[]
     */
    public function getSortedAttributes(): \Iterator
    {
        $iterator = $this->attributes->getIterator();
        assert($iterator instanceof ArrayIterator);
        $iterator->uasort(
            function (InvoiceAttribute $a, InvoiceAttribute $b) {
                return $a->getAttribute()->getId() <=> $b->getAttribute()->getId();
            }
        );

        return $iterator;
    }

    public function isUncollectible(): bool
    {
        return $this->uncollectible;
    }

    public function setUncollectible(bool $uncollectible): void
    {
        $this->uncollectible = $uncollectible;
    }

    public function getPaymentToken(): ?PaymentToken
    {
        return $this->paymentToken;
    }

    public function setPaymentToken(?PaymentToken $paymentToken): void
    {
        $this->paymentToken = $paymentToken;
    }

    public function isProforma(): bool
    {
        return $this->isProforma;
    }

    public function setIsProforma(bool $isProforma): void
    {
        $this->isProforma = $isProforma;
    }

    public function getProformaInvoice(): ?Invoice
    {
        return $this->proformaInvoice;
    }

    public function setProformaInvoice(?Invoice $proformaInvoice): void
    {
        $this->proformaInvoice = $proformaInvoice;
    }

    public function getGeneratedInvoice(): ?Invoice
    {
        return $this->generatedInvoice;
    }

    public function setGeneratedInvoice(?Invoice $generatedInvoice): void
    {
        $this->generatedInvoice = $generatedInvoice;
    }

    public function getProformaInvoiceTemplate(): ?ProformaInvoiceTemplate
    {
        return $this->proformaInvoiceTemplate;
    }

    public function setProformaInvoiceTemplate(?ProformaInvoiceTemplate $proformaInvoiceTemplate): void
    {
        $this->proformaInvoiceTemplate = $proformaInvoiceTemplate;
    }

    public function getTemplate(): FinancialTemplateInterface
    {
        if ($this->isProforma()) {
            return $this->getProformaInvoiceTemplate() ?? $this->getOrganization()->getProformaInvoiceTemplate();
        }

        return $this->getInvoiceTemplate() ?? $this->getOrganization()->getInvoiceTemplate();
    }

    public function getTaxableSupplyDate(): ?\DateTimeImmutable
    {
        return $this->taxableSupplyDate;
    }

    public function setTaxableSupplyDate(?\DateTimeImmutable $taxableSupplyDate): void
    {
        $this->taxableSupplyDate = $taxableSupplyDate;
    }

    public function isEditable(): bool
    {
        return in_array($this->invoiceStatus, [self::DRAFT, self::UNPAID], true)
            || (
                $this->invoiceStatus === self::PAID
                && round($this->total, $this->currency->getFractionDigits()) <= 0.0
            );
    }
}
