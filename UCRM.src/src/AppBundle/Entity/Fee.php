<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\QuoteItem;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\FeeRepository")
 */
class Fee implements LoggableInterface, ParentLoggableInterface
{
    public const TYPE_LATE_FEE = 1;
    public const TYPE_SETUP_FEE = 2;
    public const TYPE_EARLY_TERMINATION_FEE = 3;

    public const PRICE_TYPE_CURRENCY = 1;
    public const PRICE_TYPE_PERCENTAGE = 2;

    public const PRICE_TYPES = [
        self::PRICE_TYPE_CURRENCY => 'Currency',
        self::PRICE_TYPE_PERCENTAGE => 'Percentage',
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="fee_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     */
    protected $type;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=500)
     * @Assert\Length(max = 500)
     * @Assert\NotBlank()
     */
    protected $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="invoice_label", type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $invoiceLabel;

    /**
     * @var float
     *
     * @ORM\Column(name="price", type="float")
     */
    protected $price;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_date", type="datetime_utc")
     */
    protected $createdDate;

    /**
     * @var Client
     *
     * @ORM\ManyToOne(targetEntity="Client", inversedBy="fees")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="client_id", nullable=false, onDelete="CASCADE")
     */
    protected $client;

    /**
     * @var Service|null
     *
     * @ORM\ManyToOne(targetEntity="Service")
     * @ORM\JoinColumn(referencedColumnName="service_id", onDelete="SET NULL")
     */
    protected $service;

    /**
     * @var bool
     *
     * @ORM\Column(name="taxable", type="boolean", options={"default":false})
     */
    protected $taxable;

    /**
     * @var Invoice
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Financial\Invoice")
     * @ORM\JoinColumn(name="due_invoice_id", referencedColumnName="invoice_id", nullable=true)
     */
    protected $dueInvoice;

    /**
     * @var bool
     *
     * @ORM\Column(name="invoiced", type="boolean", options={"default":false})
     */
    protected $invoiced = false;

    /**
     * @var Collection|QuoteItem[]
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Financial\QuoteItemFee", mappedBy="fee")
     */
    protected $quoteItems;

    public function __construct()
    {
        $this->quoteItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): void
    {
        $this->type = $type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getInvoiceLabel(): ?string
    {
        return $this->invoiceLabel;
    }

    public function setInvoiceLabel(?string $invoiceLabel): void
    {
        $this->invoiceLabel = $invoiceLabel;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    public function getCreatedDate(): \DateTime
    {
        return $this->createdDate;
    }

    public function setCreatedDate(\DateTime $createdDate): void
    {
        $this->createdDate = $createdDate;
    }

    public function isTaxable(): bool
    {
        return $this->taxable;
    }

    public function setTaxable(bool $taxable): void
    {
        $this->taxable = $taxable;
    }

    public function isInvoiced(): bool
    {
        return $this->invoiced;
    }

    public function setInvoiced(bool $invoiced): void
    {
        $this->invoiced = $invoiced;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): void
    {
        $this->service = $service;
    }

    public function setDueInvoice(?Invoice $dueInvoice): void
    {
        $this->dueInvoice = $dueInvoice;
    }

    public function getDueInvoice(): ?Invoice
    {
        return $this->dueInvoice;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDeleteMessage(): array
    {
        $message['logMsg'] = [
            'message' => 'Fee %s deleted',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogInsertMessage()
    {
        $message['logMsg'] = [
            'message' => 'Fee %s added',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogIgnoredColumns()
    {
        return [
            'invoiced',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getLogClient()
    {
        return $this->getClient();
    }

    /**
     * {@inheritdoc}
     */
    public function getLogSite()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogParentEntity()
    {
        return $this->getClient();
    }

    /**
     * {@inheritdoc}
     */
    public function getLogUpdateMessage()
    {
        $message['logMsg'] = [
            'id' => $this->getId(),
            'message' => $this->getName(),
            'entity' => self::class,
        ];

        return $message;
    }

    public function addQuoteItem(QuoteItem $quoteItem): void
    {
        $this->quoteItems[] = $quoteItem;
    }

    public function removeQuoteItem(QuoteItem $quoteItem): void
    {
        $this->quoteItems->removeElement($quoteItem);
    }

    /**
     * @return Collection|QuoteItem[]
     */
    public function getQuoteItems(): Collection
    {
        return $this->quoteItems;
    }
}
