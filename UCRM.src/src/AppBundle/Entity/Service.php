<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use AppBundle\Entity\Financial\InvoiceItemService;
use AppBundle\Entity\Financial\QuoteItemService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\Mapping as ORM;
use Location\Coordinate;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ServiceRepository")
 * @ORM\Table(
 *      indexes={
 *          @ORM\Index(columns={"deleted_at"}),
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class Service implements LoggableInterface, ParentLoggableInterface, SoftDeleteLoggableInterface
{
    use SoftDeleteableTrait;

    const INVOICING_BACKWARDS = 1;
    const INVOICING_FORWARDS = 2;

    const INVOICING_PERIOD_TYPE = [
        self::INVOICING_BACKWARDS => 'Backward',
        self::INVOICING_FORWARDS => 'Forward',
    ];

    const POSSIBLE_INVOICING_PERIOD_TYPE = [
        self::INVOICING_BACKWARDS,
        self::INVOICING_FORWARDS,
    ];

    const CONTRACT_OPEN = 1;
    const CONTRACT_CLOSED = 2;

    const CONTRACT_LENGTH_TYPE = [
        self::CONTRACT_OPEN => 'Open end contract',
        self::CONTRACT_CLOSED => 'Close end contract',
    ];

    const DISCOUNT_NONE = 0;
    const DISCOUNT_PERCENTAGE = 1;
    const DISCOUNT_FIXED = 2;

    const DISCOUNT_TYPE = [
        self::DISCOUNT_NONE => 'No discount',
        self::DISCOUNT_PERCENTAGE => 'Percentage discount',
        self::DISCOUNT_FIXED => 'Fixed discount',
    ];

    const DISCOUNT_TYPE_MODAL = [
        self::DISCOUNT_PERCENTAGE => 'Percentage discount',
        self::DISCOUNT_FIXED => 'Fixed discount',
    ];

    const POSSIBLE_DISCOUNT_TYPES = [
        self::DISCOUNT_NONE,
        self::DISCOUNT_PERCENTAGE,
        self::DISCOUNT_FIXED,
    ];

    const STATUS_PREPARED = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_ENDED = 2;
    const STATUS_SUSPENDED = 3;
    const STATUS_PREPARED_BLOCKED = 4;
    const STATUS_OBSOLETE = 5;
    const STATUS_DEFERRED = 6;
    const STATUS_QUOTED = 7;

    const SERVICE_STATUSES = [
        self::STATUS_PREPARED => 'Prepared',
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_ENDED => 'Ended',
        self::STATUS_SUSPENDED => 'Suspended',
        self::STATUS_PREPARED_BLOCKED => 'Prepared and blocked',
        self::STATUS_OBSOLETE => 'Obsolete',
        self::STATUS_DEFERRED => 'Deferred',
        self::STATUS_QUOTED => 'Quoted',
    ];

    const POSSIBLE_STATUSES = [
        self::STATUS_PREPARED,
        self::STATUS_ACTIVE,
        self::STATUS_ENDED,
        self::STATUS_SUSPENDED,
        self::STATUS_PREPARED_BLOCKED,
        self::STATUS_OBSOLETE,
        self::STATUS_DEFERRED,
        self::STATUS_QUOTED,
    ];

    const ACTIVE_STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_SUSPENDED,
    ];

    const DELETABLE_STATUSES = [
        self::STATUS_ENDED,
        self::STATUS_PREPARED,
        self::STATUS_PREPARED_BLOCKED,
        self::STATUS_QUOTED,
    ];

    const INTERNAL_STATUSES = [
        self::STATUS_OBSOLETE,
        self::STATUS_DEFERRED,
        self::STATUS_QUOTED,
    ];

    const FULL_EDITABLE_STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_SUSPENDED,
        self::STATUS_PREPARED,
        self::STATUS_PREPARED_BLOCKED,
        self::STATUS_QUOTED,
    ];

    public const INVOICING_PERIOD_START_DAY = [
        1 => '1',
        2 => '2',
        3 => '3',
        4 => '4',
        5 => '5',
        6 => '6',
        7 => '7',
        8 => '8',
        9 => '9',
        10 => '10',
        11 => '11',
        12 => '12',
        13 => '13',
        14 => '14',
        15 => '15',
        16 => '16',
        17 => '17',
        18 => '18',
        19 => '19',
        20 => '20',
        21 => '21',
        22 => '22',
        23 => '23',
        24 => '24',
        25 => '25',
        26 => '26',
        27 => '27',
        28 => '28',
        31 => 'last',
    ];

    public const POSSIBLE_INVOICING_PERIOD_START_DAY = [
        1,
        2,
        3,
        4,
        5,
        6,
        7,
        8,
        9,
        10,
        11,
        12,
        13,
        14,
        15,
        16,
        17,
        18,
        19,
        20,
        21,
        22,
        23,
        24,
        25,
        26,
        27,
        28,
        31,
    ];

    const VALIDATION_GROUP_DEFAULT = 'Default';
    const VALIDATION_GROUP_SERVICE = 'Service';
    const VALIDATION_GROUP_INVOICE_PREVIEW = 'InvoicePreview';

    /**
     * @var int
     *
     * @ORM\Column(name="service_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var Client
     *
     * @ORM\ManyToOne(targetEntity="Client", inversedBy="services")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="client_id", nullable=false, onDelete="CASCADE")
     * @Assert\NotNull()
     */
    protected $client;

    /**
     * @var string|null
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=true)
     * @Assert\Length(max = 100)
     */
    protected $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="street1", type="string", length=250, nullable=true)
     * @Assert\Length(max = 250)
     */
    protected $street1;

    /**
     * @var string|null
     *
     * @ORM\Column(name="street2", type="string", length=250, nullable=true)
     * @Assert\Length(max = 250)
     */
    protected $street2;

    /**
     * @var string|null
     *
     * @ORM\Column(name="city", type="string", length=250, nullable=true)
     * @Assert\Length(max = 250)
     */
    protected $city;

    /**
     * @var Country|null
     *
     * @ORM\ManyToOne(targetEntity="Country")
     * @ORM\JoinColumn(name="country_id", referencedColumnName="country_id", nullable=true)
     */
    protected $country;

    /**
     * @var State|null
     *
     * @ORM\ManyToOne(targetEntity="State")
     * @ORM\JoinColumn(name="state_id", referencedColumnName="state_id", nullable=true)
     */
    protected $state;

    /**
     * @var string|null
     *
     * @ORM\Column(name="zip_code", type="string", length=20, nullable=true)
     * @Assert\Length(max = 20)
     */
    protected $zipCode;

    /**
     * @var string|null
     *
     * @ORM\Column(name="note", type="text", nullable=true)
     */
    protected $note;

    /**
     * @var float|null
     *
     * @ORM\Column(name="address_gps_lat", type="float", nullable=true)
     * @Assert\Range(
     *     min = -90,
     *     max = 90
     * )
     */
    protected $addressGpsLat;

    /**
     * @var float|null
     *
     * @ORM\Column(name="address_gps_lon", type="float", nullable=true)
     * @Assert\Range(
     *     min = -180,
     *     max = 180
     * )
     */
    protected $addressGpsLon;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": false})
     */
    protected $isAddressGpsCustom = false;

    /**
     * @var Tariff|null
     *
     * @Assert\NotNull(groups={Service::VALIDATION_GROUP_DEFAULT, Service::VALIDATION_GROUP_INVOICE_PREVIEW})
     * @ORM\ManyToOne(targetEntity="Tariff", inversedBy="services")
     * @ORM\JoinColumn(name="tariff_id", referencedColumnName="tariff_id", nullable=false)
     */
    protected $tariff;

    /**
     * @var TariffPeriod
     *
     * @Assert\Expression(
     *     expression="value or not this.getTariff()",
     *     message="This value should not be null.",
     *     groups={Service::VALIDATION_GROUP_DEFAULT, Service::VALIDATION_GROUP_INVOICE_PREVIEW}
     * )
     * @ORM\ManyToOne(targetEntity="TariffPeriod", inversedBy="services")
     * @ORM\JoinColumn(name="period_id", referencedColumnName="period_id", nullable=false)
     */
    protected $tariffPeriod;

    /**
     * @var float|null
     *
     * @ORM\Column(type="float", nullable=true)
     */
    protected $individualPrice;

    /**
     * @var string|null
     *
     * @ORM\Column(name="invoice_label", type="string", length=100, nullable=true)
     * @Assert\Length(max = 100)
     */
    protected $invoiceLabel;

    /**
     * @var string|null
     *
     * @ORM\Column(name="contract_id", type="string", length=50, nullable=true)
     * @Assert\Length(max = 50)
     */
    protected $contractId;

    /**
     * @var int
     *
     * @ORM\Column(name="contract_length_type", type="integer")
     * @Assert\Choice(choices = {Service::CONTRACT_OPEN, Service::CONTRACT_CLOSED}, strict=true)
     * @Assert\NotNull()
     */
    protected $contractLengthType;

    /**
     * @var int|null
     *
     * @ORM\Column(name="minimum_contract_length_months", type="integer", nullable=true)
     */
    protected $minimumContractLengthMonths;

    /**
     * @var Fee|null
     *
     * @ORM\OneToOne(targetEntity="Fee", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinColumn(referencedColumnName="fee_id", onDelete="SET NULL")
     */
    protected $setupFee;

    /**
     * @var Fee|null
     *
     * @ORM\OneToOne(targetEntity="Fee", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinColumn(referencedColumnName="fee_id", onDelete="SET NULL")
     */
    protected $earlyTerminationFee;

    /**
     * @var float|null
     *
     * @ORM\Column(type="float", nullable=true)
     */
    protected $earlyTerminationFeePrice;

    /**
     * NOT used currently, uses global setting instead.
     *
     * @var bool|null
     *
     * @ORM\Column(name="stop_invoicing", type="boolean", nullable=true)
     */
    protected $stopInvoicing;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="active_from", type="date")
     * @Assert\NotNull()
     */
    protected $activeFrom;

    /**
     * Last day when service is still active.
     *
     * @var \DateTime|null
     *
     * @ORM\Column(name="active_to", type="date", nullable=true)
     * @Assert\Expression(expression="this.getStatus() === constant('AppBundle\\Entity\\Service::STATUS_ENDED') or not value or value >= this.getActiveFrom()", message="The service must be active for at least one day.")
     */
    protected $activeTo;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="contract_end_date", type="date", nullable=true)
     * @Assert\Expression(
     *     expression="this.getContractLengthType() === constant('AppBundle\\Entity\\Service::CONTRACT_OPEN') or value",
     *     message="Contract end date is required for closed contract."
     * )
     */
    protected $contractEndDate;

    /**
     * @var int
     *
     * @ORM\Column(name="discount_type", type="integer", options={"unsigned":true, "default":0})
     * @Assert\Choice(choices=Service::POSSIBLE_DISCOUNT_TYPES, strict=true)
     */
    protected $discountType = 0;

    /**
     * @var float|null
     *
     * @ORM\Column(name="discount_value", type="float", nullable=true)
     * @Assert\Expression(
     *     expression="this.getDiscountType() === 0 or (this.getDiscountType() !== 0 and value !== null)",
     *     message="This field is required"
     * )
     */
    protected $discountValue;

    /**
     * @var string|null
     *
     * @ORM\Column(name="discount_invoice_label", type="string", length=100, nullable=true)
     * @Assert\Length(max = 100)
     */
    protected $discountInvoiceLabel;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="discount_from", type="date", nullable=true)
     */
    protected $discountFrom;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="discount_to", type="date", nullable=true)
     */
    protected $discountTo;

    /**
     * @var Tax|null
     *
     * @ORM\ManyToOne(targetEntity="Tax")
     * @ORM\JoinColumn(name="tax_id1", referencedColumnName="tax_id", nullable=true)
     */
    protected $tax1;

    /**
     * @var Tax|null
     *
     * @ORM\ManyToOne(targetEntity="Tax")
     * @ORM\JoinColumn(name="tax_id2", referencedColumnName="tax_id", nullable=true)
     *
     * @Assert\Expression(expression="not value or this.getTax1() != value", message="This tax is already selected.")
     */
    protected $tax2;

    /**
     * @var Tax|null
     *
     * @ORM\ManyToOne(targetEntity="Tax")
     * @ORM\JoinColumn(name="tax_id3", referencedColumnName="tax_id", nullable=true)
     *
     * @Assert\Expression(expression="not value or (this.getTax1() != value and this.getTax2() != value)", message="This tax is already selected.")
     */
    protected $tax3;

    /**
     * @var Collection|ServiceSurcharge[]
     *
     * @Assert\Valid()
     * @ORM\OneToMany(targetEntity="ServiceSurcharge", mappedBy="service", cascade={"remove", "persist"})
     * @ORM\JoinColumn(name="service_surcharge_id", referencedColumnName="service_surcharge_id", nullable=true)
     * @ORM\OrderBy({"id" = "ASC"})
     */
    protected $serviceSurcharges;

    /**
     * Not NULL value means Service is suspended.
     *
     * @var ServiceStopReason|null
     *
     * @ORM\ManyToOne(targetEntity="ServiceStopReason")
     * @ORM\JoinColumn(name="reason_id", referencedColumnName="reason_id", nullable=true)
     */
    protected $stopReason;

    /**
     * @var Collection|InvoiceItemService[]
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Financial\InvoiceItemService", mappedBy="service")
     * @ORM\JoinColumn(name="service_id", referencedColumnName="service_id")
     */
    protected $invoiceItemsService;

    /**
     * @var Collection|QuoteItemService[]
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Financial\QuoteItemService", mappedBy="service")
     * @ORM\JoinColumn(name="service_id", referencedColumnName="service_id")
     */
    protected $quoteItemsService;

    /**
     * DateTime when service will be suspended again. Used for suspension postpone feature.
     *
     * @var \DateTime|null
     *
     * @ORM\Column(name="suspended_from", type="datetime_utc", nullable=true)
     */
    protected $suspendedFrom;

    /**
     * @var Collection|Financial\Invoice[]
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Financial\Invoice")
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(referencedColumnName="service_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(referencedColumnName="invoice_id", onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"dueDate" = "ASC"})
     */
    protected $suspendedByInvoices;

    /**
     * @var bool
     *
     * @ORM\Column(name="suspend_postponed_by_client", type="boolean", options={"default":false})
     */
    protected $suspendPostponedByClient = false;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint", options={"unsigned":true})
     * @Assert\Choice(choices=Service::POSSIBLE_STATUSES, strict=true)
     */
    protected $status;

    /**
     * Date when invoices will start generating.
     *
     * @var \DateTime
     *
     * @ORM\Column(name="invoicing_start", type="date")
     *
     * @Assert\NotNull(groups={Service::VALIDATION_GROUP_DEFAULT, Service::VALIDATION_GROUP_INVOICE_PREVIEW})
     * @Assert\Expression(expression="this.getStatus() === constant('AppBundle\\Entity\\Service::STATUS_ENDED') or not this.getActiveTo() or value <= this.getActiveTo()", message="Invoicing must start before 'Active to'.")
     */
    protected $invoicingStart;

    /**
     * Type of invoicing period. Can be backwards or forwards.
     *
     * @var int
     *
     * @ORM\Column(name="invoicing_period_type", type="integer")
     *
     * @Assert\NotNull(groups={Service::VALIDATION_GROUP_DEFAULT, Service::VALIDATION_GROUP_INVOICE_PREVIEW})
     * @Assert\Choice(choices=Service::POSSIBLE_INVOICING_PERIOD_TYPE, strict=true)
     */
    protected $invoicingPeriodType;

    /**
     * Day of invoicing period. E.g. 15 = 15th to 14th cycle.
     *
     * @var int
     *
     * @ORM\Column(name="invoicing_period_start_day", type="integer", options={"default": 1})
     *
     * @Assert\Choice(choices=Service::POSSIBLE_INVOICING_PERIOD_START_DAY, strict=true)
     */
    protected $invoicingPeriodStartDay = 1;

    /**
     * End of last invoiced period.
     *
     * @var \DateTime|null
     *
     * @ORM\Column(name="invoicing_last_period_end", type="date", nullable=true)
     */
    protected $invoicingLastPeriodEnd;

    /**
     * @var \DateTime|null
     *
     * @deprecated No longer used, backwards compatibility only
     *
     * @ORM\Column(name="prev_invoicing_day", type="date", nullable=true)
     */
    protected $prevInvoicingDay;

    /**
     * Date when next invoice will be generated.
     *
     * @var \DateTime
     *
     * @ORM\Column(name="next_invoicing_day", type="date")
     */
    protected $nextInvoicingDay;

    /**
     * Adjustment of $nextInvoicingDay - number of days to move the date.
     *
     * @var int
     *
     * @ORM\Column(name="next_invoicing_day_adjustment", type="integer", options={"default": 0})
     * @Assert\NotNull()
     * @Assert\Range(min="0", max="730")
     */
    protected $nextInvoicingDayAdjustment = 0;

    /**
     * Whether to create separate invoice for pro-rated part, or wait for whole next period.
     *
     * @var bool
     *
     * @ORM\Column(name="invoicing_prorated_separately", type="boolean", options={"default": true})
     */
    protected $invoicingProratedSeparately = true;

    /**
     * Whether to couple services with matching period on single invoice.
     *
     * @var bool
     *
     * @ORM\Column(name="invoicing_separately", type="boolean", options={"default": false})
     */
    protected $invoicingSeparately = false;

    /**
     * Whether to automatically approve and send invoice after invoice is generated automatically.
     *
     * @var bool|null
     *
     * @ORM\Column(name="send_emails_automatically", type="boolean", nullable=true)
     */
    protected $sendEmailsAutomatically;

    /**
     * Whether to use credit automatically to pay for new invoices.
     *
     * @var bool
     *
     * @ORM\Column(name="use_credit_automatically", type="boolean", options={"default": true})
     */
    protected $useCreditAutomatically = true;

    /**
     * @var Collection|ServiceDevice[]
     *
     * @Assert\Valid()
     * @ORM\OneToMany(targetEntity="ServiceDevice", mappedBy="service", cascade={"remove", "persist"})
     */
    protected $serviceDevices;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     */
    protected $hasOutage = false;

    /**
     * Do NOT add inverse side to this relation. It can cause many requests during hydration because inverse side
     * of x-to-one can never be lazy. See UnitOfWork::createEntity().
     *
     * @var Service|null
     *
     * @ORM\OneToOne(targetEntity="Service", cascade={"remove"})
     * @ORM\JoinColumn(name="superseded_by_service_id", referencedColumnName="service_id", onDelete="CASCADE")
     */
    protected $supersededByService;

    /**
     * Used to backup original activeTo date when there is a deferred change.
     *
     * Note: Don't use this to detect whether the service has a deferred change. It can still be null.
     *
     * @var \DateTime|null
     *
     * @ORM\Column(type="date", nullable=true)
     */
    protected $activeToBackup;

    /**
     * @var Collection|Selectable
     *
     * @ORM\OneToMany(targetEntity="SuspensionPeriod", mappedBy="service", cascade={"remove", "persist"})
     * @ORM\OrderBy({"since" = "ASC"})
     */
    protected $suspensionPeriods;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=15, nullable=true)
     * @Assert\Length(max = 15)
     */
    protected $fccBlockId;

    /**
     * @var Collection|PaymentPlan[]
     *
     * @ORM\OneToMany(targetEntity="PaymentPlan", mappedBy="service")
     */
    protected $paymentPlans;

    public function __construct()
    {
        $this->resetCollections();
    }

    public function resetCollections()
    {
        $this->serviceSurcharges = new ArrayCollection();
        $this->invoiceItemsService = new ArrayCollection();
        $this->quoteItemsService = new ArrayCollection();
        $this->serviceDevices = new ArrayCollection();
        $this->suspensionPeriods = new ArrayCollection();
        $this->suspendedByInvoices = new ArrayCollection();
        $this->paymentPlans = new ArrayCollection();
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function calculateStatus()
    {
        if ($this->isDeleted() || in_array($this->status, self::INTERNAL_STATUSES, true)) {
            return;
        }

        $today = new \DateTime('midnight');

        if ($this->activeFrom > $today && null !== $this->stopReason) {
            $this->status = self::STATUS_PREPARED_BLOCKED;
        } elseif ($this->activeFrom > $today) {
            $this->status = self::STATUS_PREPARED;
        } elseif ($this->activeTo !== null && $this->activeTo < $today && $this->supersededByService === null) {
            $this->status = self::STATUS_ENDED;
        } elseif ($this->stopReason !== null) {
            $this->status = self::STATUS_SUSPENDED;
        } else {
            $this->status = self::STATUS_ACTIVE;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @internal used only for dummy data
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        if (null === $this->name && $this->tariff) {
            return $this->tariff->getName();
        }

        return $this->name;
    }

    /**
     * Hack for Service(...)Type forms to access name directly.
     * Refactoring getName to not have logic would be too time consuming.
     */
    public function getNameDirectly(): ?string
    {
        return $this->name;
    }

    /**
     * Setter needed to allow usage of "property_path".
     *
     * @see getNameDirectly
     */
    public function setNameDirectly(?string $name): void
    {
        $this->name = $name;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function getIndividualPrice(): ?float
    {
        return $this->individualPrice;
    }

    public function setIndividualPrice(?float $individualPrice): void
    {
        $this->individualPrice = $individualPrice;
    }

    public function getPrice(): float
    {
        if (null !== $this->individualPrice) {
            return $this->individualPrice;
        }
        if (null !== $this->tariffPeriod) {
            return $this->tariffPeriod->getPrice() ?? 0.0;
        }

        return 0.0;
    }

    /**
     * Get discount price for single tariff period, discarding current date.
     */
    public function getDiscountPriceSinglePeriod(): float
    {
        if ($this->discountType === self::DISCOUNT_FIXED) {
            return $this->discountValue ?? 0.0;
        }

        if ($this->discountType === self::DISCOUNT_PERCENTAGE) {
            $price = $this->getPrice();

            return $this->discountValue
                ? $price * ($this->discountValue / 100)
                : 0.0;
        }

        return 0.0;
    }

    public function getSurchargesPrice(): float
    {
        $price = 0.0;

        /** @var ServiceSurcharge $item */
        foreach ($this->serviceSurcharges as $item) {
            $price += $item->getInheritedPrice();
        }

        return $price;
    }

    public function setInvoiceLabel(?string $invoiceLabel): void
    {
        $this->invoiceLabel = $invoiceLabel;
    }

    public function getInvoiceLabel(): ?string
    {
        return $this->invoiceLabel;
    }

    public function getInvoiceLabelForView(): ?string
    {
        return $this->getInvoiceLabel() ?: $this->getTariff()->getInvoiceLabelOrName();
    }

    public function setContractId(?string $contractId): void
    {
        $this->contractId = $contractId;
    }

    public function getContractId(): ?string
    {
        return $this->contractId;
    }

    public function setContractLengthType(?int $contractLengthType): void
    {
        $this->contractLengthType = $contractLengthType;
    }

    public function getContractLengthType(): ?int
    {
        return $this->contractLengthType;
    }

    public function setMinimumContractLengthMonths(?int $minimumContractLengthMonths): void
    {
        $this->minimumContractLengthMonths = $minimumContractLengthMonths;
    }

    public function getMinimumContractLengthMonths(): ?int
    {
        return $this->minimumContractLengthMonths;
    }

    public function getSetupFee(): ?Fee
    {
        return $this->setupFee;
    }

    public function setSetupFee(?Fee $setupFee): void
    {
        $this->setupFee = $setupFee;
    }

    public function getEarlyTerminationFee(): ?Fee
    {
        return $this->earlyTerminationFee;
    }

    public function setEarlyTerminationFee(?Fee $earlyTerminationFee): void
    {
        $this->earlyTerminationFee = $earlyTerminationFee;
    }

    public function getEarlyTerminationFeePrice(): ?float
    {
        return $this->earlyTerminationFeePrice;
    }

    public function setEarlyTerminationFeePrice(?float $earlyTerminationFeePrice): void
    {
        $this->earlyTerminationFeePrice = $earlyTerminationFeePrice;
    }

    public function setStopInvoicing(?bool $stopInvoicing): void
    {
        $this->stopInvoicing = $stopInvoicing;
    }

    public function getStopInvoicing(): ?bool
    {
        return $this->stopInvoicing;
    }

    public function setActiveFrom(?\DateTime $activeFrom): void
    {
        $this->activeFrom = $activeFrom;
    }

    public function getActiveFrom(): ?\DateTime
    {
        return $this->activeFrom;
    }

    public function setActiveTo(?\DateTime $activeTo): void
    {
        $this->activeTo = $activeTo;
    }

    public function getActiveTo(): ?\DateTime
    {
        return $this->activeTo;
    }

    public function setContractEndDate(?\DateTime $contractEndDate): void
    {
        $this->contractEndDate = $contractEndDate;
    }

    public function getContractEndDate(): ?\DateTime
    {
        return $this->contractEndDate;
    }

    public function setDiscountType(?int $discountType): void
    {
        $this->discountType = $discountType;
    }

    public function getDiscountType(): int
    {
        return is_int($this->discountType) ? $this->discountType : self::DISCOUNT_NONE;
    }

    public function hasDiscount(): bool
    {
        return $this->discountType !== self::DISCOUNT_NONE;
    }

    public function setDiscountValue(?float $discountValue): void
    {
        $this->discountValue = $discountValue;
    }

    public function getDiscountValue(): ?float
    {
        return $this->discountValue;
    }

    public function setDiscountInvoiceLabel(?string $discountInvoiceLabel): void
    {
        $this->discountInvoiceLabel = $discountInvoiceLabel;
    }

    public function getDiscountInvoiceLabel(): ?string
    {
        return $this->discountInvoiceLabel;
    }

    public function setDiscountFrom(?\DateTime $discountFrom): void
    {
        $this->discountFrom = $discountFrom;
    }

    public function getDiscountFrom(): ?\DateTime
    {
        return $this->discountFrom;
    }

    public function setDiscountTo(?\DateTime $discountTo): void
    {
        $this->discountTo = $discountTo;
    }

    public function getDiscountTo(): ?\DateTime
    {
        return $this->discountTo;
    }

    public function setClient(?Client $client): void
    {
        $this->client = $client;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setTariff(?Tariff $tariff): void
    {
        $this->tariff = $tariff;
    }

    public function getTariff(): ?Tariff
    {
        return $this->tariff;
    }

    public function setTariffPeriod(?TariffPeriod $tariffPeriod): void
    {
        $this->tariffPeriod = $tariffPeriod;
    }

    public function getTariffPeriod(): ?TariffPeriod
    {
        return $this->tariffPeriod;
    }

    public function getTariffPeriodMonths(): int
    {
        if ($this->getTariffPeriod()) {
            return $this->getTariffPeriod()->getPeriod();
        }

        return 1;
    }

    public function setTax1(?Tax $tax1): void
    {
        $this->tax1 = $tax1;
    }

    public function getTax1(): ?Tax
    {
        return $this->tax1;
    }

    public function setTax2(?Tax $tax2): void
    {
        $this->tax2 = $tax2;
    }

    public function getTax2(): ?Tax
    {
        return $this->tax2;
    }

    public function setTax3(?Tax $tax3): void
    {
        $this->tax3 = $tax3;
    }

    public function getTax3(): ?Tax
    {
        return $this->tax3;
    }

    /**
     * @return ArrayCollection|ServiceIp[]
     */
    public function getServiceIps(): Collection
    {
        $ips = new ArrayCollection();

        if (null !== $this->getServiceDevices()) {
            foreach ($this->getServiceDevices() as $device) {
                foreach ($device->getServiceIps() as $serviceDeviceIp) {
                    $ips[] = $serviceDeviceIp;
                }
            }
        }

        return $ips;
    }

    public function addServiceSurcharge(ServiceSurcharge $serviceSurcharge): void
    {
        $this->serviceSurcharges[] = $serviceSurcharge;
    }

    public function removeServiceSurcharge(ServiceSurcharge $serviceSurcharge): void
    {
        $this->serviceSurcharges->removeElement($serviceSurcharge);
    }

    /**
     * @return Collection|ServiceSurcharge[]
     */
    public function getServiceSurcharges(): Collection
    {
        return $this->serviceSurcharges;
    }

    public function setStreet1(?string $street1): void
    {
        $this->street1 = $street1;
    }

    public function getStreet1(): ?string
    {
        return $this->street1;
    }

    public function setStreet2(?string $street2): void
    {
        $this->street2 = $street2;
    }

    public function getStreet2(): ?string
    {
        return $this->street2;
    }

    public function setCity(?string $city): void
    {
        $this->city = $city;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setZipCode(?string $zipCode): void
    {
        $this->zipCode = $zipCode;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setCountry(?Country $country): void
    {
        $this->country = $country;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setState(?State $state): void
    {
        $this->state = $state;
    }

    public function getState(): ?State
    {
        return $this->state;
    }

    public function setAddressGpsLat(?float $addressGpsLat): void
    {
        $this->addressGpsLat = $addressGpsLat;
    }

    public function getAddressGpsLat(): ?float
    {
        return $this->addressGpsLat;
    }

    public function setAddressGpsLon(?float $addressGpsLon): void
    {
        $this->addressGpsLon = $addressGpsLon;
    }

    public function getAddressGpsLon(): ?float
    {
        return $this->addressGpsLon;
    }

    public function isAddressGpsCustom(): bool
    {
        return $this->isAddressGpsCustom;
    }

    public function setIsAddressGpsCustom(bool $isAddressGpsCustom): void
    {
        $this->isAddressGpsCustom = $isAddressGpsCustom;
    }

    public function isAddressSameAsClient(): bool
    {
        $countryId = $this->country ? $this->country->getId() : null;
        $clientCountryId = $this->client->getCountry() ? $this->client->getCountry()->getId() : null;
        $stateId = $this->state ? $this->state->getId() : null;
        $clientStateId = $this->client->getState() ? $this->client->getState()->getId() : null;

        return $this->street1 === $this->client->getStreet1()
            && $this->street2 === $this->client->getStreet2()
            && $this->city === $this->client->getCity()
            && $this->zipCode === $this->client->getZipCode()
            && $countryId === $clientCountryId
            && $stateId === $clientStateId
            && (float) $this->addressGpsLat === (float) $this->client->getAddressGpsLat()
            && (float) $this->addressGpsLon === (float) $this->client->getAddressGpsLon();
    }

    public function getAddress(bool $short = false): array
    {
        $address = [
            $this->getStreet1(),
            $this->getStreet2(),
            $this->getCity(),
            $this->getZipCode(),
            $this->getState() ? ($short ? $this->getState()->getCode() : $this->getState()->getName()) : null,
            $short ? null : ($this->getCountry() ? $this->getCountry()->getName() : null),
        ];

        return array_filter($address);
    }

    public function getAddressString(): string
    {
        return implode(', ', $this->getAddress(false));
    }

    public function getShortAddressString(): string
    {
        return implode(', ', $this->getAddress(true));
    }

    public function getAddressForGeocoding(): string
    {
        return implode(
            ', ',
            array_filter(
                [
                    $this->getStreet1(),
                    $this->getCity(),
                    $this->getState() ? $this->getState()->getName() : null,
                    $this->getZipCode(),
                    $this->getCountry() ? $this->getCountry()->getName() : null,
                ]
            )
        );
    }

    public function setStatus(?int $status): void
    {
        $this->status = $status;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStopReason(?ServiceStopReason $stopReason): void
    {
        $this->stopReason = $stopReason;
    }

    public function getStopReason(): ?ServiceStopReason
    {
        return $this->stopReason;
    }

    public function setSuspendedFrom(?\DateTime $suspendedFrom): void
    {
        $this->suspendedFrom = $suspendedFrom;
    }

    public function getSuspendedFrom(): ?\DateTime
    {
        return $this->suspendedFrom;
    }

    /**
     * @return Financial\Invoice[]|Collection
     */
    public function getSuspendedByInvoices(): Collection
    {
        return $this->suspendedByInvoices;
    }

    public function addSuspendedByInvoice(Financial\Invoice $invoice): void
    {
        $this->suspendedByInvoices[] = $invoice;
    }

    public function removeSuspendedByInvoice(Financial\Invoice $invoice): void
    {
        $this->suspendedByInvoices->removeElement($invoice);
    }

    public function setSuspendPostponedByClient(bool $suspendPostponedByClient = false): void
    {
        $this->suspendPostponedByClient = $suspendPostponedByClient;
    }

    public function getSuspendPostponedByClient(): ?bool
    {
        return $this->suspendPostponedByClient;
    }

    public function addInvoiceItemsService(InvoiceItemService $invoiceItemsService): void
    {
        $this->invoiceItemsService[] = $invoiceItemsService;
    }

    public function removeInvoiceItemsService(InvoiceItemService $invoiceItemsService): void
    {
        $this->invoiceItemsService->removeElement($invoiceItemsService);
    }

    /**
     * @return Collection|InvoiceItemService[]
     */
    public function getInvoiceItemsService(): Collection
    {
        return $this->invoiceItemsService;
    }

    public function addQuoteItemsService(QuoteItemService $quoteItemService): void
    {
        $this->quoteItemsService[] = $quoteItemService;
    }

    public function removeQuoteItemsService(QuoteItemService $quoteItemService): void
    {
        $this->quoteItemsService->removeElement($quoteItemService);
    }

    /**
     * @return Collection|QuoteItemService[]
     */
    public function getQuoteItemsService(): Collection
    {
        return $this->quoteItemsService;
    }

    public function getInvoicingStart(): ?\DateTime
    {
        return $this->invoicingStart;
    }

    public function setInvoicingStart(?\DateTime $invoicingStart): void
    {
        $this->invoicingStart = $invoicingStart;
    }

    public function getInvoicingPeriodType(): ?int
    {
        return $this->invoicingPeriodType;
    }

    public function setInvoicingPeriodType(?int $invoicingPeriodType): void
    {
        $this->invoicingPeriodType = $invoicingPeriodType;
    }

    public function getInvoicingPeriodStartDay(): ?int
    {
        return $this->invoicingPeriodStartDay;
    }

    public function setInvoicingPeriodStartDay(?int $invoicingPeriodStartDay): void
    {
        $this->invoicingPeriodStartDay = $invoicingPeriodStartDay;
    }

    public function getInvoicingLastPeriodEnd(): ?\DateTime
    {
        return $this->invoicingLastPeriodEnd;
    }

    public function setInvoicingLastPeriodEnd(?\DateTime $invoicingLastPeriodEnd): void
    {
        $this->invoicingLastPeriodEnd = $invoicingLastPeriodEnd;
    }

    public function getNextInvoicingDay(): ?\DateTime
    {
        return $this->nextInvoicingDay;
    }

    public function setNextInvoicingDay(?\DateTime $nextInvoicingDay): void
    {
        $this->nextInvoicingDay = $nextInvoicingDay;
    }

    public function getNextInvoicingDayAdjustment(): ?int
    {
        return $this->nextInvoicingDayAdjustment;
    }

    public function setNextInvoicingDayAdjustment(?int $nextInvoicingDayAdjustment): void
    {
        $this->nextInvoicingDayAdjustment = $nextInvoicingDayAdjustment;
    }

    public function isInvoicingProratedSeparately(): ?bool
    {
        return $this->invoicingProratedSeparately;
    }

    public function setInvoicingProratedSeparately(?bool $invoicingProratedSeparately): void
    {
        $this->invoicingProratedSeparately = $invoicingProratedSeparately;
    }

    public function isInvoicingSeparately(): ?bool
    {
        return $this->invoicingSeparately;
    }

    public function setInvoicingSeparately(?bool $invoicingSeparately): void
    {
        $this->invoicingSeparately = $invoicingSeparately;
    }

    public function isSendEmailsAutomatically(): ?bool
    {
        return $this->sendEmailsAutomatically;
    }

    public function setSendEmailsAutomatically(?bool $sendEmailsAutomatically): void
    {
        $this->sendEmailsAutomatically = $sendEmailsAutomatically;
    }

    public function isUseCreditAutomatically(): ?bool
    {
        return $this->useCreditAutomatically;
    }

    public function setUseCreditAutomatically(?bool $useCreditAutomatically): void
    {
        $this->useCreditAutomatically = $useCreditAutomatically;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDeleteMessage(): array
    {
        $message['logMsg'] = [
            'message' => 'Service %s deleted',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogArchiveMessage(): array
    {
        $message['logMsg'] = [
            'message' => 'Service %s archived',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogRestoreMessage(): array
    {
        $message['logMsg'] = [
            'message' => 'Service %s restored',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogInsertMessage(): array
    {
        $message['logMsg'] = [
            'message' => 'Service %s added',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogIgnoredColumns(): array
    {
        return [
            'prevInvoicingDay',
            'nextInvoicingDay',
            'status',
            'hasOutage',
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
            'message' => $this->getName(),
            'entity' => self::class,
        ];

        return $message;
    }

    public function getInvoicingProratedSeparately(): bool
    {
        return $this->invoicingProratedSeparately;
    }

    public function getInvoicingSeparately(): bool
    {
        return $this->invoicingSeparately;
    }

    public function getUseCreditAutomatically(): bool
    {
        return $this->useCreditAutomatically;
    }

    public function addServiceDevice(ServiceDevice $serviceDevice): void
    {
        $this->serviceDevices[] = $serviceDevice;
    }

    public function removeServiceDevice(ServiceDevice $serviceDevice): void
    {
        $this->serviceDevices->removeElement($serviceDevice);
    }

    /**
     * @return Collection|ServiceDevice[]
     */
    public function getServiceDevices()
    {
        return $this->serviceDevices;
    }

    public function getConnectedTo(): string
    {
        if (! $this->serviceDevices->count()) {
            return '';
        }

        $connections = [];
        foreach ($this->serviceDevices as $serviceDevice) {
            $connections[] = sprintf(
                '%s - %s',
                $serviceDevice->getInterface()->getDevice()->getName(),
                $serviceDevice->getInterface()->getName()
            );
        }

        $connections = array_unique($connections);

        return implode(', ', $connections);
    }

    public function hasOutage(): bool
    {
        return $this->hasOutage;
    }

    public function setHasOutage(?bool $hasOutage): void
    {
        $this->hasOutage = $hasOutage;
    }

    public function hasTax(): bool
    {
        return $this->tax1 || $this->tax2 || $this->tax3;
    }

    public function hasAllTaxes(): bool
    {
        return $this->tax1 && $this->tax2 && $this->tax3;
    }

    public function getSupersededByService(): ?Service
    {
        return $this->supersededByService;
    }

    public function setSupersededByService(?Service $supersededByService): void
    {
        $this->supersededByService = $supersededByService;
    }

    public function getActiveToBackup(): ?\DateTime
    {
        return $this->activeToBackup;
    }

    public function setActiveToBackup(?\DateTime $activeToBackup)
    {
        $this->activeToBackup = $activeToBackup;
    }

    public function getSuspensionPeriods(): Collection
    {
        return $this->suspensionPeriods;
    }

    public function addSuspensionPeriod(SuspensionPeriod $suspensionPeriod): void
    {
        $this->suspensionPeriods->add($suspensionPeriod);
    }

    public function removeSuspensionPeriod(SuspensionPeriod $suspensionPeriod): void
    {
        $this->suspensionPeriods->removeElement($suspensionPeriod);
    }

    public function getCurrentSuspensionPeriod(): ?SuspensionPeriod
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->isNull('until'));

        return $this->suspensionPeriods->matching($criteria)->first() ?: null;
    }

    public function canSetSetupFee(): bool
    {
        return ! $this->getSetupFee() || ! $this->getSetupFee()->isInvoiced();
    }

    public function getFccBlockId(): ?string
    {
        return $this->fccBlockId;
    }

    public function setFccBlockId(?string $fccBlockId): void
    {
        $this->fccBlockId = $fccBlockId;
    }

    /**
     * @return Collection|PaymentPlan[]
     */
    public function getPaymentPlans(): Collection
    {
        return $this->paymentPlans;
    }

    /**
     * @return Collection|PaymentPlan[]
     */
    public function getActivePaymentPlans(): Collection
    {
        $plans = new ArrayCollection();

        foreach ($this->paymentPlans as $paymentPlan) {
            if ($paymentPlan->isActive()) {
                $plans->add($paymentPlan);
            }
        }

        return $plans;
    }

    public function hasActiveLinkedPaymentPlan(): bool
    {
        $paymentPlans = $this->getActivePaymentPlans();
        foreach ($paymentPlans as $paymentPlan) {
            if ($paymentPlan->isActive() && $paymentPlan->isLinked()) {
                return true;
            }
        }

        return false;
    }

    public function setPaymentPlans(Collection $paymentPlans): void
    {
        $this->paymentPlans = $paymentPlans;
    }

    public function getGpsCoordinate(): ?Coordinate
    {
        if (! $this->addressGpsLat || ! $this->addressGpsLon) {
            return null;
        }

        try {
            return new Coordinate($this->addressGpsLat, $this->addressGpsLon);
        } catch (\InvalidArgumentException $exception) {
            return null;
        }
    }

    public function hasAddressGps(): bool
    {
        return $this->getAddressGpsLat() !== null && $this->getAddressGpsLon() !== null;
    }
}
