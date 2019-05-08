<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use AppBundle\Component\Validator\Constraints as CustomAssert;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\Quote;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Location\Coordinate;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use TicketingBundle\Entity\Ticket;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ClientRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(
 *      indexes={
 *          @ORM\Index(columns={"user_ident_int", "user_ident"}),
 *          @ORM\Index(columns={"deleted_at"}),
 *      }
 * )
 * @UniqueEntity("userIdent", message="This client ID already exists in our system.")
 */
class Client implements LoggableInterface, ParentLoggableInterface, SoftDeleteLoggableInterface
{
    use SoftDeleteableTrait;

    public const TYPE_RESIDENTIAL = 1;
    public const TYPE_COMPANY = 2;

    public const CLIENT_TYPE = [
        self::TYPE_RESIDENTIAL => 'Residential',
        self::TYPE_COMPANY => 'Company',
    ];

    public const CLIENT_TYPES = [
        self::TYPE_RESIDENTIAL,
        self::TYPE_COMPANY,
    ];

    public const INVITATION_EMAIL_SEND_STATUS_PENDING = 1;
    public const INVITATION_EMAIL_SEND_STATUS_SENT = 2;

    /**
     * @var int
     *
     * @ORM\Column(name="client_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="user_ident", type="string", unique=true, nullable=true, length=255, options={"comment":"User id defined by administrator or imported from previous ISP"})
     * @Assert\Length(max = 255, groups={"Default", "CsvClient"})
     */
    protected $userIdent;

    /**
     * @var int
     *
     * @ORM\Column(name="user_ident_int", type="bigint", nullable=true, options={"comment":"User id casted to integer for ordering"})
     */
    protected $userIdentInt;

    /**
     * @var string
     *
     * @ORM\Column(name="previous_isp", type="string", nullable=true, length=500)
     * @Assert\Length(max = 500, groups={"Default", "CsvClient"})
     */
    protected $previousIsp;

    /**
     * @var int
     *
     * @ORM\Column(name="client_type", type="integer")
     * @Assert\NotBlank()
     * @Assert\Range(min = 1, max = 2)
     * @Assert\Choice(choices=Client::CLIENT_TYPES, strict=true)
     */
    protected $clientType;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": false})
     * @CustomAssert\ClientCanBeConvertedToLead()
     */
    protected $isLead = false;

    /**
     * @var string|null
     *
     * @ORM\Column(name="company_name", type="string", length=255, nullable=true)
     * @Assert\Length(max = 255, groups={"Default", "CsvClient"})
     * @Assert\Expression(
     *     expression="this.getClientType() === constant('AppBundle\\Entity\\Client::TYPE_RESIDENTIAL') or value",
     *     message="This field is required.",
     *     groups={"Default", "CsvClient"}
     * )
     */
    protected $companyName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="company_registration_number", type="string", length=50, nullable=true)
     * @Assert\Length(max = 50, groups={"Default", "CsvClient"})
     */
    protected $companyRegistrationNumber;

    /**
     * @var string|null
     *
     * @ORM\Column(name="company_tax_id", type="string", length=50, nullable=true)
     * @Assert\Length(max = 50, groups={"Default", "CsvClient"})
     */
    protected $companyTaxId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="company_website", type="string", length=400, nullable=true)
     * @Assert\Length(max = 400, groups={"Default", "CsvClient"})
     */
    protected $companyWebsite;

    /**
     * @var string|null
     *
     * @ORM\Column(name="street1", type="string", length=250, nullable=true)
     * @Assert\Length(max = 250, groups={"Default", "CsvClient"})
     */
    protected $street1;

    /**
     * @var string|null
     *
     * @ORM\Column(name="street2", type="string", length=250, nullable=true)
     * @Assert\Length(max = 250, groups={"Default", "CsvClient"})
     */
    protected $street2;

    /**
     * @var string|null
     *
     * @ORM\Column(name="city", type="string", length=250, nullable=true)
     * @Assert\Length(max = 250, groups={"Default", "CsvClient"})
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
     * @Assert\Length(max = 20, groups={"Default", "CsvClient"})
     */
    protected $zipCode;

    /**
     * @var float|null
     *
     * @ORM\Column(type="float", nullable=true)
     * @Assert\Range(
     *     min = -90,
     *     max = 90,
     *     groups={"Default", "CsvClient"}
     * )
     */
    protected $addressGpsLat;

    /**
     * @var float|null
     *
     * @ORM\Column(type="float", nullable=true)
     * @Assert\Range(
     *     min = -180,
     *     max = 180,
     *     groups={"Default", "CsvClient"}
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
     * @var string|null
     *
     * @ORM\Column(name="invoice_street1", type="string", length=250, nullable=true)
     * @Assert\Length(max = 250, groups={"Default", "CsvClient"})
     */
    protected $invoiceStreet1;

    /**
     * @var string|null
     *
     * @ORM\Column(name="invoice_street2", type="string", length=250, nullable=true)
     * @Assert\Length(max = 250, groups={"Default", "CsvClient"})
     */
    protected $invoiceStreet2;

    /**
     * @var string|null
     *
     * @ORM\Column(name="invoice_city", type="string", length=250, nullable=true)
     * @Assert\Length(max = 250, groups={"Default", "CsvClient"})
     */
    protected $invoiceCity;

    /**
     * @var State|null
     *
     * @ORM\ManyToOne(targetEntity="State")
     * @ORM\JoinColumn(name="invoice_state_id", referencedColumnName="state_id", nullable=true)
     */
    protected $invoiceState;

    /**
     * @var Country|null
     *
     * @ORM\ManyToOne(targetEntity="Country")
     * @ORM\JoinColumn(name="invoice_country_id", referencedColumnName="country_id", nullable=true)
     */
    protected $invoiceCountry;

    /**
     * @var string|null
     *
     * @ORM\Column(name="invoice_zip_code", type="string", length=20, nullable=true)
     * @Assert\Length(max = 20, groups={"Default", "CsvClient"})
     */
    protected $invoiceZipCode;

    /**
     * @var bool
     *
     * @ORM\Column(name="invoice_address_same_as_contact", type="boolean", options={"default":true})
     */
    protected $invoiceAddressSameAsContact = true;

    /**
     * @var string
     *
     * @ORM\Column(name="note", type="text", nullable=true)
     */
    protected $note;

    /**
     * @deprecated
     *
     * @var bool
     *
     * @ORM\Column(name="send_invoice_by_email", type="boolean", options={"default":true})
     */
    protected $sendInvoiceByEmail = true;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="send_invoice_by_post", type="boolean", options={"default":false}, nullable = true)
     */
    protected $sendInvoiceByPost;

    /**
     * @deprecated
     *
     * @var int
     *
     * @ORM\Column(name="invoice_day", type="integer", options={"default":1})
     */
    protected $invoiceDay = 1;

    /**
     * @var int|null
     *
     * @ORM\Column(name="invoice_maturity_days", type="integer", nullable=true)
     * @Assert\LessThanOrEqual(value = 36500)
     * @Assert\GreaterThanOrEqual(value = 0)
     */
    protected $invoiceMaturityDays;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="stop_service_due", type="boolean", options={"default":true}, nullable=true)
     */
    protected $stopServiceDue;

    /**
     * @var int|null
     *
     * @ORM\Column(name="stop_service_due_days", type="integer", nullable=true)
     */
    protected $stopServiceDueDays;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $lateFeeDelayDays;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Organization", inversedBy="clients")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="organization_id", nullable=false)
     * @Assert\NotNull()
     */
    protected $organization;

    /**
     * @var Collection|ClientBankAccount[]
     *
     * @ORM\OneToMany(targetEntity="ClientBankAccount", mappedBy="client", cascade={"persist", "remove"})
     */
    protected $bankAccounts;

    /**
     * @var User
     *
     * @ORM\OneToOne(targetEntity="User", inversedBy="client", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="user_id", nullable=false, onDelete="CASCADE")
     *
     * @Assert\Valid()
     */
    protected $user;

    /**
     * @var Collection|Service[]
     *
     * @ORM\OneToMany(targetEntity="Service", mappedBy="client", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="client_id", referencedColumnName="client_id")
     */
    protected $services;

    /**
     * @var Collection|Invoice[]
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Financial\Invoice", mappedBy="client", cascade={"remove"})
     * @ORM\JoinColumn(name="client_id", referencedColumnName="client_id")
     */
    protected $invoices;

    /**
     * @var Collection|Quote[]
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Financial\Quote", mappedBy="client", cascade={"remove"})
     * @ORM\JoinColumn(name="client_id", referencedColumnName="client_id")
     */
    protected $quotes;

    /**
     * @ORM\ManyToOne(targetEntity="Tax")
     * @ORM\JoinColumn(name="tax_id1", referencedColumnName="tax_id", nullable=true)
     */
    protected $tax1;

    /**
     * @ORM\ManyToOne(targetEntity="Tax")
     * @ORM\JoinColumn(name="tax_id2", referencedColumnName="tax_id", nullable=true)
     */
    protected $tax2;

    /**
     * @ORM\ManyToOne(targetEntity="Tax")
     * @ORM\JoinColumn(name="tax_id3", referencedColumnName="tax_id", nullable=true)
     */
    protected $tax3;

    /**
     * @var float
     *
     * @ORM\Column(name="balance", type="float", options={"default":0})
     */
    protected $balance = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="account_standings_credit", type="float", options={"default":0})
     */
    protected $accountStandingsCredit = 0;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     */
    protected $accountStandingsRefundableCredit = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="account_standings_outstanding", type="float", options={"default":0})
     */
    protected $accountStandingsOutstanding = 0;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="registration_date", type="date")
     *
     * @Assert\NotNull()
     */
    protected $registrationDate;

    /**
     * @var Collection|Payment[]
     *
     * @ORM\OneToMany(targetEntity="Payment", mappedBy="client", cascade={"remove"})
     * @ORM\JoinColumn(name="client_id", referencedColumnName="client_id")
     */
    protected $payments;

    /**
     * @var Collection|PaymentPlan[]
     *
     * @ORM\OneToMany(targetEntity="PaymentPlan", mappedBy="client", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="client_id", referencedColumnName="client_id")
     */
    protected $paymentPlans;

    /**
     * @var Collection|Credit[]
     *
     * @ORM\OneToMany(targetEntity="Credit", mappedBy="client", cascade={"remove"})
     * @ORM\JoinColumn(name="client_id", referencedColumnName="client_id")
     */
    protected $credits;

    /**
     * @var Collection|Refund[]
     *
     * @ORM\OneToMany(targetEntity="Refund", mappedBy="client", cascade={"remove"})
     * @ORM\JoinColumn(name="client_id", referencedColumnName="client_id")
     */
    protected $refunds;

    /**
     * @var string|null
     *
     * @ORM\Column(name="company_contact_first_name", type="text", nullable=true)
     */
    protected $companyContactFirstName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="company_contact_last_name", type="text", nullable=true)
     */
    protected $companyContactLastName;

    /**
     * @var Collection|Fee[]
     *
     * @ORM\OneToMany(targetEntity="Fee", mappedBy="client", cascade={"remove"})
     * @ORM\JoinColumn(name="client_id", referencedColumnName="client_id")
     */
    protected $fees;

    /**
     * @var float
     *
     * @ORM\Column(name="average_monthly_payment", type="float", nullable=true)
     */
    protected $averageMonthlyPayment;

    /**
     * @ORM\Column(name="invitation_email_sent_date", type="date", nullable=true)
     */
    protected $invitationEmailSentDate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="stripe_customer_id", type="string", length=100, nullable=true)
     */
    protected $stripeCustomerId;

    /**
     * @var string
     *
     * @ORM\Column(name="paypal_customer_id", type="string", length=100, nullable=true)
     */
    protected $paypalCustomerId;

    /**
     * @var string
     *
     * @ORM\Column(name="anet_customer_profile_id", type="string", length=100, nullable=true)
     */
    protected $anetCustomerProfileId;

    /**
     * @var string
     *
     * @ORM\Column(name="anet_customer_payment_profile_id", type="string", length=100, nullable=true)
     */
    protected $anetCustomerPaymentProfileId;

    /**
     * @var Collection|EmailLog[]
     *
     * @ORM\OneToMany(targetEntity="EmailLog", mappedBy="client", cascade={"remove"})
     * @ORM\JoinColumn(name="client_id", referencedColumnName="client_id")
     */
    protected $emailLogs;

    /**
     * @var Collection|Document[]
     *
     * @ORM\OneToMany(targetEntity="Document", mappedBy="client", cascade={"persist","remove"})
     * @ORM\JoinColumn(name="client_id", referencedColumnName="client_id")
     */
    protected $documents;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     */
    protected $hasSuspendedService = false;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     */
    protected $hasOutage = false;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     */
    protected $hasOverdueInvoice = false;

    /**
     * @var Collection|ClientContact[]
     *
     * @ORM\OneToMany(targetEntity="ClientContact", mappedBy="client", cascade={"persist"}, orphanRemoval=true)
     * @ORM\OrderBy({"id" = "ASC"})
     * @Assert\Valid()
     */
    protected $contacts;

    /**
     * @var Collection|ClientAttribute[]
     *
     * @ORM\OneToMany(targetEntity="ClientAttribute", mappedBy="client", cascade={"persist"}, orphanRemoval=true)
     * @Assert\Valid()
     */
    protected $attributes;

    /**
     * @var int
     *
     * @ORM\Column(type = "smallint", nullable=true, options = {"unsigned": true})
     */
    protected $invitationEmailSendStatus;

    /**
     * @var Collection|ClientTag[]
     *
     * @ORM\ManyToMany(targetEntity="ClientTag", inversedBy="clients")
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(referencedColumnName="client_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"name" = "ASC"})
     */
    protected $clientTags;

    /**
     * @var Collection|Ticket[]
     *
     * @ORM\OneToMany(targetEntity="TicketingBundle\Entity\Ticket", mappedBy="client", cascade={"remove"})
     * @ORM\JoinColumn(name="client_id", referencedColumnName="client_id")
     */
    protected $tickets;

    /**
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $generateProformaInvoices;

    public function __construct()
    {
        $this->bankAccounts = new ArrayCollection();
        $this->services = new ArrayCollection();
        $this->invoices = new ArrayCollection();
        $this->quotes = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->credits = new ArrayCollection();
        $this->refunds = new ArrayCollection();
        $this->fees = new ArrayCollection();
        $this->emailLogs = new ArrayCollection();
        $this->paymentPlans = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->contacts = new ArrayCollection();
        $this->attributes = new ArrayCollection();
        $this->clientTags = new ArrayCollection();

        $this->user = new User();
        $this->user->setClient($this);
        $this->user->setUsername(null);
        $this->user->setRole(User::ROLE_CLIENT);
        $this->user->setFirstLoginToken(md5($this->getId() . random_bytes(10)));
        // new client is not active by default (registration is needed)
        $this->user->setIsActive(false);
        $this->user->setPassword('');
        $this->tickets = new ArrayCollection();
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function updateUserIdentInt(): void
    {
        $this->userIdentInt = ctype_digit($this->userIdent) && bccomp($this->userIdent, (string) PHP_INT_MAX) !== 1
            ? (int) $this->userIdent
            : null;
    }

    public function resetDataByType(): void
    {
        if ($this->getClientType() === self::TYPE_COMPANY) {
            $this->getUser()->setFirstName(null);
            $this->getUser()->setLastName(null);
        } else {
            $this->setCompanyName(null);
            $this->setCompanyContactFirstName(null);
            $this->setCompanyContactLastName(null);
            $this->setCompanyRegistrationNumber(null);
            $this->setCompanyTaxId(null);
            $this->setCompanyWebsite(null);
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setClientType(int $clientType): void
    {
        $this->clientType = $clientType;
    }

    public function getClientType(): ?int
    {
        return $this->clientType;
    }

    /**
     * @param string|null $companyName
     */
    public function setCompanyName($companyName): void
    {
        $this->companyName = $companyName;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    /**
     * @param string|null $companyTaxId
     */
    public function setCompanyTaxId($companyTaxId): void
    {
        $this->companyTaxId = $companyTaxId;
    }

    public function getCompanyTaxId(): ?string
    {
        return $this->companyTaxId;
    }

    /**
     * @param string|null $companyWebsite
     */
    public function setCompanyWebsite($companyWebsite): void
    {
        $this->companyWebsite = $companyWebsite;
    }

    public function getCompanyWebsite(): ?string
    {
        return $this->companyWebsite;
    }

    public function setStreet1(?string $street1): void
    {
        $this->street1 = $street1;
    }

    public function getStreet1(): ?string
    {
        return $this->street1;
    }

    /**
     * @param string $street2
     */
    public function setStreet2($street2): void
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

    public function getAddressGpsLat(): ?float
    {
        return $this->addressGpsLat;
    }

    public function setAddressGpsLat(?float $addressGpsLat): void
    {
        $this->addressGpsLat = $addressGpsLat;
    }

    public function getAddressGpsLon(): ?float
    {
        return $this->addressGpsLon;
    }

    public function setAddressGpsLon(?float $addressGpsLon): void
    {
        $this->addressGpsLon = $addressGpsLon;
    }

    public function isAddressGpsCustom(): bool
    {
        return $this->isAddressGpsCustom;
    }

    public function setIsAddressGpsCustom(bool $isAddressGpsCustom): void
    {
        $this->isAddressGpsCustom = $isAddressGpsCustom;
    }

    /**
     * @param string $note
     */
    public function setNote($note): void
    {
        $this->note = $note;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setOrganization(?Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function addBankAccount(ClientBankAccount $bankAccount): void
    {
        $this->bankAccounts[] = $bankAccount;
    }

    public function removeBankAccount(ClientBankAccount $bankAccount)
    {
        $this->bankAccounts->removeElement($bankAccount);
    }

    /**
     * @return Collection|ClientBankAccount[]
     */
    public function getBankAccounts()
    {
        return $this->bankAccounts;
    }

    public function getCompanyRegistrationNumber(): ?string
    {
        return $this->companyRegistrationNumber;
    }

    /**
     * @param string|null $companyRegistrationNumber
     */
    public function setCompanyRegistrationNumber($companyRegistrationNumber): void
    {
        $this->companyRegistrationNumber = $companyRegistrationNumber;
    }

    public function isCompany(): bool
    {
        return $this->clientType === self::TYPE_COMPANY;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUserIdent(?string $userIdent): void
    {
        $this->userIdent = $userIdent;
    }

    public function getUserIdent(): ?string
    {
        return $this->userIdent;
    }

    public function setPreviousIsp($previousIsp): void
    {
        $this->previousIsp = $previousIsp;
    }

    public function getPreviousIsp(): ?string
    {
        return $this->previousIsp;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): void
    {
        $this->country = $country;
    }

    public function getInvoiceStreet1(): ?string
    {
        return $this->invoiceStreet1;
    }

    public function setInvoiceStreet1(?string $invoiceStreet1): void
    {
        $this->invoiceStreet1 = $invoiceStreet1;
    }

    public function getInvoiceStreet2(): ?string
    {
        return $this->invoiceStreet2;
    }

    public function setInvoiceStreet2(?string $invoiceStreet2): void
    {
        $this->invoiceStreet2 = $invoiceStreet2;
    }

    public function getInvoiceCity(): ?string
    {
        return $this->invoiceCity;
    }

    public function setInvoiceCity(?string $invoiceCity): void
    {
        $this->invoiceCity = $invoiceCity;
    }

    public function getInvoiceState(): ?State
    {
        return $this->invoiceState;
    }

    public function setInvoiceState(?State $invoiceState): void
    {
        $this->invoiceState = $invoiceState;
    }

    public function getInvoiceCountry(): ?Country
    {
        return $this->invoiceCountry;
    }

    public function setInvoiceCountry(?Country $invoiceCountry): void
    {
        $this->invoiceCountry = $invoiceCountry;
    }

    public function getInvoiceZipCode(): ?string
    {
        return $this->invoiceZipCode;
    }

    public function setInvoiceZipCode(?string $invoiceZipCode): void
    {
        $this->invoiceZipCode = $invoiceZipCode;
    }

    public function isInvoiceAddressSameAsContact(): bool
    {
        return $this->invoiceAddressSameAsContact;
    }

    public function setInvoiceAddressSameAsContact($invoiceAddressSameAsContact): void
    {
        $this->invoiceAddressSameAsContact = (bool) $invoiceAddressSameAsContact;
    }

    public function getSendInvoiceByPost(): ?bool
    {
        return $this->sendInvoiceByPost;
    }

    public function setSendInvoiceByPost($sendInvoiceByPost): void
    {
        $this->sendInvoiceByPost = $sendInvoiceByPost !== null ? (bool) $sendInvoiceByPost : null;
    }

    public function getInvoiceMaturityDays(): ?int
    {
        return $this->invoiceMaturityDays;
    }

    /**
     * @param int|null $invoiceMaturityDays
     */
    public function setInvoiceMaturityDays($invoiceMaturityDays)
    {
        $this->invoiceMaturityDays = $invoiceMaturityDays;
    }

    public function getStopServiceDueDays(): ?int
    {
        return $this->stopServiceDueDays;
    }

    /**
     * @param int|null $stopServiceDueDays
     *
     * @return $this
     */
    public function setStopServiceDueDays($stopServiceDueDays): self
    {
        $this->stopServiceDueDays = $stopServiceDueDays;

        return $this;
    }

    public function getLateFeeDelayDays(): ?int
    {
        return $this->lateFeeDelayDays;
    }

    public function setLateFeeDelayDays(?int $lateFeeDelayDays): void
    {
        $this->lateFeeDelayDays = $lateFeeDelayDays;
    }

    public function getState(): ?State
    {
        return $this->state;
    }

    public function setState(?State $state): void
    {
        $this->state = $state;
    }

    public function addService(Service $service): void
    {
        $this->services[] = $service;
    }

    public function removeService(Service $service)
    {
        $this->services->removeElement($service);
    }

    /**
     * @deprecated use getNotDeletedServices instead
     *
     * @return Collection|Service[]
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    /**
     * @return Collection|Service[]
     */
    public function getNotDeletedServices(): Collection
    {
        return $this->services->matching(
            Criteria::create()->where(Criteria::expr()->isNull('deletedAt'))
        );
    }

    /**
     * @return ArrayCollection|ServiceIp[]
     */
    public function getUnassignedServiceIps(): ArrayCollection
    {
        $deletedServices = $this->services->matching(
            Criteria::create()->where(Criteria::expr()->neq('deletedAt', null))
        );

        $serviceIps = [];
        /** @var Service $deletedService */
        foreach ($deletedServices as $deletedService) {
            $serviceIps += $deletedService->getServiceIps()->toArray();
        }

        return new ArrayCollection($serviceIps);
    }

    public function setTax1(Tax $tax1 = null): void
    {
        $this->tax1 = $tax1;
    }

    public function getTax1(): ?Tax
    {
        return $this->tax1;
    }

    public function setTax2(Tax $tax2 = null): void
    {
        $this->tax2 = $tax2;
    }

    public function getTax2(): ?Tax
    {
        return $this->tax2;
    }

    public function setTax3(Tax $tax3 = null): void
    {
        $this->tax3 = $tax3;
    }

    public function getTax3(): ?Tax
    {
        return $this->tax3;
    }

    /**
     * @param float $balance
     */
    public function setBalance($balance): void
    {
        $this->balance = $balance;
    }

    public function getBalance(): ?float
    {
        return $this->balance;
    }

    public function getInvoiceAddressSameAsContact(): bool
    {
        return $this->invoiceAddressSameAsContact;
    }

    public function addInvoice(Invoice $invoice): void
    {
        if ($this->invoices->contains($invoice)) {
            return;
        }

        $this->invoices->add($invoice);
    }

    public function removeInvoice(Invoice $invoice): void
    {
        if (! $this->invoices->contains($invoice)) {
            return;
        }

        $this->invoices->removeElement($invoice);
    }

    /**
     * @return Collection|Invoice[]
     */
    public function getInvoices()
    {
        return $this->invoices;
    }

    public function addQuote(Quote $quote): void
    {
        if ($this->quotes->contains($quote)) {
            return;
        }

        $this->quotes->add($quote);
    }

    public function removeQuote(Quote $quote): void
    {
        if (! $this->quotes->contains($quote)) {
            return;
        }

        $this->quotes->removeElement($quote);
    }

    /**
     * @return Collection|Quote[]
     */
    public function getQuotes(): Collection
    {
        return $this->quotes;
    }

    public function setRegistrationDate(?\DateTime $registrationDate): void
    {
        $this->registrationDate = $registrationDate;
    }

    public function getRegistrationDate(): ?\DateTime
    {
        return $this->registrationDate;
    }

    public function addPayment(Payment $payment): void
    {
        if ($this->payments->contains($payment)) {
            return;
        }

        $this->payments->add($payment);
    }

    public function removePayment(Payment $payment): void
    {
        if (! $this->payments->contains($payment)) {
            return;
        }

        $this->payments->removeElement($payment);
    }

    /**
     * @return Collection|Payment[]
     */
    public function getPayments()
    {
        return $this->payments;
    }

    public function getNameForView(): ?string
    {
        switch ($this->clientType) {
            case self::TYPE_RESIDENTIAL:
                return $this->getUser()->getFirstName() . ' ' . $this->getUser()->getLastName();

            case self::TYPE_COMPANY:
                return $this->companyName;
        }

        return null;
    }

    public function getCompanyContactNameForView(): ?string
    {
        if ($this->clientType === self::TYPE_COMPANY && ($this->companyContactFirstName || $this->companyContactLastName)) {
            return trim($this->companyContactFirstName . ' ' . $this->companyContactLastName);
        }

        return null;
    }

    public function getFirstName(): ?string
    {
        if ($this->clientType === self::TYPE_RESIDENTIAL) {
            return $this->getUser()->getFirstName();
        }

        return null;
    }

    public function getLastName(): ?string
    {
        if ($this->clientType === self::TYPE_RESIDENTIAL) {
            return $this->getUser()->getLastName();
        }

        return null;
    }

    public function addCredit(Credit $credit): void
    {
        if ($this->credits->contains($credit)) {
            return;
        }

        $this->credits->add($credit);
    }

    public function removeCredit(Credit $credit): void
    {
        if (! $this->credits->contains($credit)) {
            return;
        }

        $this->credits->removeElement($credit);
    }

    /**
     * @return Collection|Credit[]
     */
    public function getCredits()
    {
        return $this->credits->matching(
            Criteria::create()->orderBy(
                [
                    'payment' => Criteria::ASC,
                ]
            )
        );
    }

    /**
     * @param string|null $companyContactFirstName
     */
    public function setCompanyContactFirstName($companyContactFirstName): void
    {
        $this->companyContactFirstName = $companyContactFirstName;
    }

    public function getCompanyContactFirstName(): ?string
    {
        return $this->companyContactFirstName;
    }

    /**
     * @param string|null $companyContactLastName
     */
    public function setCompanyContactLastName($companyContactLastName): void
    {
        $this->companyContactLastName = $companyContactLastName;
    }

    public function getCompanyContactLastName(): ?string
    {
        return $this->companyContactLastName;
    }

    /**
     * @param bool|null $stopServiceDue
     */
    public function setStopServiceDue($stopServiceDue): void
    {
        $this->stopServiceDue = $stopServiceDue;
    }

    public function getStopServiceDue(): ?bool
    {
        return $this->stopServiceDue;
    }

    public function addFee(Fee $fee): void
    {
        $this->fees[] = $fee;
    }

    public function removeFee(Fee $fee)
    {
        $this->fees->removeElement($fee);
    }

    /**
     * @return Collection|Fee[]
     */
    public function getFees()
    {
        return $this->fees;
    }

    public function setAverageMonthlyPayment(?float $averageMonthlyPayment): void
    {
        $this->averageMonthlyPayment = $averageMonthlyPayment;
    }

    public function getAverageMonthlyPayment(): ?float
    {
        return $this->averageMonthlyPayment;
    }

    /**
     * @param \DateTime|null $invitationEmailSentDate
     */
    public function setInvitationEmailSentDate($invitationEmailSentDate): void
    {
        $this->invitationEmailSentDate = $invitationEmailSentDate;
    }

    public function getInvitationEmailSentDate(): ?\DateTime
    {
        return $this->invitationEmailSentDate;
    }

    /**
     * @param float $accountStandingsCredit
     */
    public function setAccountStandingsCredit($accountStandingsCredit): void
    {
        $this->accountStandingsCredit = $accountStandingsCredit;
    }

    public function getAccountStandingsCredit(): ?float
    {
        return $this->accountStandingsCredit;
    }

    public function setAccountStandingsRefundableCredit(float $accountStandingsRefundableCredit): void
    {
        $this->accountStandingsRefundableCredit = $accountStandingsRefundableCredit;
    }

    public function getAccountStandingsRefundableCredit(): float
    {
        return $this->accountStandingsRefundableCredit;
    }

    /**
     * @param float $accountStandingsOutstanding
     */
    public function setAccountStandingsOutstanding($accountStandingsOutstanding): void
    {
        $this->accountStandingsOutstanding = $accountStandingsOutstanding;
    }

    public function getAccountStandingsOutstanding(): float
    {
        return $this->accountStandingsOutstanding;
    }

    public function getCurrencyCode(): string
    {
        return $this->getOrganization() ? $this->getOrganization()->getCurrency()->getCode() : '';
    }

    /**
     * @param int $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getAddress(bool $short = false): array
    {
        $address = [
            $this->getStreet1(),
            $this->getStreet2(),
            $this->getCity(),
            $this->getState() ? ($short ? $this->getState()->getCode() : $this->getState()->getName()) : null,
            $this->getZipCode(),
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

    public function addEmailLog(EmailLog $emailLog): void
    {
        $this->emailLogs[] = $emailLog;
    }

    public function removeEmailLog(EmailLog $emailLog)
    {
        $this->emailLogs->removeElement($emailLog);
    }

    /**
     * @return Collection|EmailLog[]
     */
    public function getEmailLogs()
    {
        return $this->emailLogs;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDeleteMessage(): array
    {
        $message['logMsg'] = [
            'message' => 'Client %s deleted',
            'replacements' => $this->getId(), // we no longer have name
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogArchiveMessage(): array
    {
        $message['logMsg'] = [
            'message' => 'Client %s archived',
            'replacements' => $this->getNameForView(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogRestoreMessage(): array
    {
        $message['logMsg'] = [
            'message' => 'Client %s restored',
            'replacements' => $this->getNameForView(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogInsertMessage(): array
    {
        $message['logMsg'] = [
            'message' => 'Client %s added',
            'replacements' => $this->getNameForView(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogIgnoredColumns(): array
    {
        return [
            'balance',
            'accountStandingsCredit',
            'accountStandingsOutstanding',
            'averageMonthlyPayment',
            'invitationEmailSentDate',
            'hasSuspendedService',
            'hasOutage',
            'hasOverdueInvoice',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getLogClient(): Client
    {
        return $this;
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
    public function getLogParentEntity()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogUpdateMessage(): array
    {
        $message['logMsg'] = [
            'id' => $this->getId(),
            'message' => $this->getNameForView(),
            'entity' => self::class,
        ];

        return $message;
    }

    public function setStripeCustomerId(?string $stripeCustomerId): void
    {
        $this->stripeCustomerId = $stripeCustomerId;
    }

    public function getStripeCustomerId(): ?string
    {
        return $this->stripeCustomerId;
    }

    /**
     * @param string $paypalCustomerId
     */
    public function setPayPalCustomerId($paypalCustomerId): void
    {
        $this->paypalCustomerId = $paypalCustomerId;
    }

    public function getPayPalCustomerId(): ?string
    {
        return $this->paypalCustomerId;
    }

    public function setAnetCustomerProfileId(string $anetCustomerProfileId = null): void
    {
        $this->anetCustomerProfileId = $anetCustomerProfileId;
    }

    public function getAnetCustomerProfileId(): ?string
    {
        return $this->anetCustomerProfileId;
    }

    public function setAnetCustomerPaymentProfileId(string $anetCustomerPaymentProfileId = null): void
    {
        $this->anetCustomerPaymentProfileId = $anetCustomerPaymentProfileId;
    }

    public function getAnetCustomerPaymentProfileId(): ?string
    {
        return $this->anetCustomerPaymentProfileId;
    }

    public function addPaymentPlan(PaymentPlan $paymentPlan): void
    {
        $this->paymentPlans[] = $paymentPlan;
    }

    public function removePaymentPlan(PaymentPlan $paymentPlan): void
    {
        $this->paymentPlans->removeElement($paymentPlan);
    }

    /**
     * @return Collection|PaymentPlan[]
     */
    public function getPaymentPlans(): Collection
    {
        return $this->paymentPlans;
    }

    /**
     * @return ArrayCollection|PaymentPlan[]
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

    public function addRefund(Refund $refund): void
    {
        $this->refunds[] = $refund;
    }

    public function removeRefund(Refund $refund)
    {
        $this->refunds->removeElement($refund);
    }

    /**
     * @return Collection|Refund[]
     */
    public function getRefunds()
    {
        return $this->refunds;
    }

    /**
     * @param bool $sendInvoiceByEmail
     */
    public function setSendInvoiceByEmail($sendInvoiceByEmail): void
    {
        $this->sendInvoiceByEmail = $sendInvoiceByEmail;
    }

    public function getSendInvoiceByEmail(): bool
    {
        return $this->sendInvoiceByEmail;
    }

    /**
     * @param int $invoiceDay
     */
    public function setInvoiceDay($invoiceDay): void
    {
        $this->invoiceDay = $invoiceDay;
    }

    public function getInvoiceDay(): ?int
    {
        return $this->invoiceDay;
    }

    public function addDocument(Document $document): void
    {
        $this->documents[] = $document;
    }

    public function removeDocument(Document $document): void
    {
        $this->documents->removeElement($document);
    }

    /**
     * @return Collection|Document[]
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function hasSuspendedService(): bool
    {
        return $this->hasSuspendedService;
    }

    public function setHasSuspendedService(bool $hasSuspendedService): void
    {
        $this->hasSuspendedService = $hasSuspendedService;
    }

    public function hasOutage(): bool
    {
        return $this->hasOutage;
    }

    public function setHasOutage(bool $hasOutage): void
    {
        $this->hasOutage = $hasOutage;
    }

    public function hasOverdueInvoice(): bool
    {
        return $this->hasOverdueInvoice;
    }

    public function setHasOverdueInvoice(bool $hasOverdueInvoice): void
    {
        $this->hasOverdueInvoice = $hasOverdueInvoice;
    }

    public function addContact(ClientContact $contact): void
    {
        $contact->setClient($this);
        $this->contacts[] = $contact;
    }

    public function removeContact(ClientContact $contact)
    {
        $this->contacts->removeElement($contact);
    }

    /**
     * @return Collection|ClientContact[]
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    public function addAttribute(ClientAttribute $attribute): void
    {
        $attribute->setClient($this);
        $this->attributes->add($attribute);
    }

    public function removeAttribute(ClientAttribute $attribute): void
    {
        $this->attributes->removeElement($attribute);
    }

    /**
     * @return Collection|ClientAttribute[]
     */
    public function getAttributes(): Collection
    {
        return $this->attributes;
    }

    /**
     * @return \Iterator|ClientAttribute[]
     */
    public function getSortedAttributes(): \Iterator
    {
        $iterator = $this->attributes->getIterator();
        assert($iterator instanceof \ArrayIterator);
        $iterator->uasort(
            static function (ClientAttribute $a, ClientAttribute $b) {
                return $a->getAttribute()->getId() <=> $b->getAttribute()->getId();
            }
        );

        return $iterator;
    }

    /**
     * @return Collection|ClientTag[]
     */
    public function getClientTags(): Collection
    {
        return $this->clientTags;
    }

    public function addClientTag(ClientTag $clientTag): void
    {
        if ($this->clientTags->contains($clientTag)) {
            return;
        }

        $this->clientTags->add($clientTag);
        $clientTag->addClient($this);
    }

    public function removeClientTag(ClientTag $clientTag): void
    {
        if (! $this->clientTags->contains($clientTag)) {
            return;
        }

        $this->clientTags->removeElement($clientTag);
        $clientTag->removeClient($this);
    }

    /**
     * @return array|string[]
     */
    public function getEmails(): array
    {
        $criteria = Criteria::create()
            ->andWhere(Criteria::expr()->neq('email', null))
            ->andWhere(Criteria::expr()->neq('email', ''));

        return $this->extractEmails($criteria);
    }

    /**
     * @return array|string[]
     */
    public function getPhones(): array
    {
        $criteria = Criteria::create()
            ->andWhere(Criteria::expr()->neq('phone', null))
            ->andWhere(Criteria::expr()->neq('phone', ''));

        return $this->extractPhones($criteria);
    }

    public function getFirstPhone(): ?string
    {
        $phones = $this->getPhones();

        return $phones ? reset($phones) : null;
    }

    /**
     * @return string[]
     */
    public function getBillingEmails(): array
    {
        return $this->contacts
            ->filter(
                static function (ClientContact $contact) {
                    $isBilling = $contact->getTypes()->exists(
                        static function ($key, ContactType $type) {
                            return $type->getId() === ContactType::IS_BILLING;
                        }
                    );

                    return $contact->getEmail() && $isBilling;
                }
            )
            ->map(
                static function (ClientContact $contact) {
                    return $contact->getEmail();
                }
            )
            ->toArray();
    }

    public function getFirstBillingEmail(): ?string
    {
        $emails = $this->getBillingEmails();

        return $emails ? reset($emails) : null;
    }

    public function hasBillingEmail(): bool
    {
        return (bool) $this->getBillingEmails();
    }

    public function getBillingPhones(): array
    {
        $billingPhones = $this->contacts
            ->filter(
                static function (ClientContact $contact) {
                    $isBilling = $contact->getTypes()->exists(
                        static function ($key, ContactType $type) {
                            return $type->getId() === ContactType::IS_BILLING;
                        }
                    );

                    return $contact->getPhone() && $isBilling;
                }
            )
            ->map(
                static function (ClientContact $contact) {
                    return $contact->getPhone();
                }
            )
            ->toArray();

        return $billingPhones ?: $this->getPhones();
    }

    public function getFirstBillingPhone(): ?string
    {
        $phones = $this->getBillingPhones();

        return $phones ? reset($phones) : null;
    }

    public function getGeneralEmails(): array
    {
        return $this->contacts
            ->filter(
                static function (ClientContact $contact) {
                    $isGeneral = $contact->getTypes()->exists(
                        static function ($key, ContactType $type) {
                            return $type->getId() === ContactType::IS_CONTACT;
                        }
                    );

                    return $contact->getEmail() && $isGeneral;
                }
            )
            ->map(
                static function (ClientContact $contact) {
                    return $contact->getEmail();
                }
            )
            ->toArray();
    }

    public function getFirstGeneralEmail(): ?string
    {
        $emails = $this->getGeneralEmails();

        return $emails ? reset($emails) : null;
    }

    public function getGeneralPhones(): array
    {
        $generalPhones = $this->contacts
            ->filter(
                static function (ClientContact $contact) {
                    $isGeneral = $contact->getTypes()->exists(
                        static function ($key, ContactType $type) {
                            return $type->getId() === ContactType::IS_CONTACT;
                        }
                    );

                    return $contact->getPhone() && $isGeneral;
                }
            )
            ->map(
                static function (ClientContact $contact) {
                    return $contact->getPhone();
                }
            )
            ->toArray();

        return $generalPhones ?: $this->getPhones();
    }

    public function getFirstGeneralPhone(): ?string
    {
        $phones = $this->getBillingPhones();

        return $phones ? reset($phones) : null;
    }

    /**
     * @return string[]
     */
    public function getContactEmails(): array
    {
        $contactEmails = $this->contacts
            ->filter(
                static function (ClientContact $contact) {
                    $isContact = $contact->getTypes()->exists(
                        static function ($key, ContactType $type) {
                            return $type->getId() === ContactType::IS_CONTACT;
                        }
                    );

                    return $contact->getEmail() && $isContact;
                }
            )
            ->map(
                static function (ClientContact $contact) {
                    return $contact->getEmail();
                }
            )
            ->toArray();

        return $contactEmails ?: $this->getEmails();
    }

    public function getInvitationEmailSendStatus(): ?int
    {
        return $this->invitationEmailSendStatus;
    }

    public function setInvitationEmailSendStatus(int $invitationEmailSendStatus): void
    {
        $this->invitationEmailSendStatus = $invitationEmailSendStatus;
    }

    public function hasAddressGps(): bool
    {
        return $this->getAddressGpsLat() !== null && $this->getAddressGpsLon() !== null;
    }

    /**
     * @return array|string[]
     */
    private function extractEmails(Criteria $criteria): array
    {
        return array_filter(
            $this->contacts->matching($criteria)
            ->map(
                static function (ClientContact $contact) {
                    return $contact->getEmail();
                }
            )->toArray()
        );
    }

    /**
     * @return array|string[]
     */
    private function extractPhones(Criteria $criteria): array
    {
        return array_filter(
            $this->contacts->matching($criteria)
            ->map(
                static function (ClientContact $contact) {
                    return $contact->getPhone();
                }
            )->toArray()
        );
    }

    public function addTicket(Ticket $ticket): void
    {
        $this->tickets[] = $ticket;
    }

    public function removeTicket(Ticket $ticket): void
    {
        $this->tickets->removeElement($ticket);
    }

    /**
     * @return Collection|Ticket[]
     */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    public function getIsLead(): ?bool
    {
        return $this->isLead;
    }

    public function setIsLead(bool $isLead): void
    {
        $this->isLead = $isLead;
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

    public function getGenerateProformaInvoices(): ?bool
    {
        return $this->generateProformaInvoices;
    }

    public function setGenerateProformaInvoices(?bool $generateProformaInvoices): void
    {
        $this->generateProformaInvoices = $generateProformaInvoices;
    }
}
