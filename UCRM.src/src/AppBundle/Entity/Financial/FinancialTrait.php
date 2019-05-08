<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Financial;

use AppBundle\Entity\Client;
use AppBundle\Entity\Country;
use AppBundle\Entity\Currency;
use AppBundle\Entity\Organization;
use AppBundle\Entity\State;
use Symfony\Component\Validator\Constraints as Assert;

trait FinancialTrait
{
    /**
     * @var float
     *
     * @ORM\Column(name="total", type="float")
     */
    protected $total;

    /**
     * @var int
     *
     * @ORM\Column(name="discount_type", type="integer", options={"unsigned":true, "default":0})
     */
    protected $discountType = 0;

    /**
     * @var float|null
     *
     * @ORM\Column(name="discount_value", type="float", nullable=true)
     * @Assert\GreaterThanOrEqual(value = 0)
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
     * @var \DateTime
     *
     * @ORM\Column(name="created_date", type="datetime_utc")
     */
    protected $createdDate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="pdf_path", type="string", length=500, nullable=true)
     * @Assert\Length(max = 500)
     */
    protected $pdfPath;

    /**
     * @var string|null
     *
     * @ORM\Column(name="client_first_name", type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $clientFirstName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="client_last_name", type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $clientLastName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="client_company_name", type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $clientCompanyName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="client_street1", type="string", length=250, nullable=true)
     * @Assert\Length(max = 250)
     */
    protected $clientStreet1;

    /**
     * @var string|null
     *
     * @ORM\Column(name="client_street2", type="string", length=250, nullable=true)
     * @Assert\Length(max = 250)
     */
    protected $clientStreet2;

    /**
     * @var string|null
     *
     * @ORM\Column(name="client_city", type="string", length=250, nullable=true)
     * @Assert\Length(max = 250)
     */
    protected $clientCity;

    /**
     * @var string|null
     *
     * @ORM\Column(name="client_zip_code", type="string", length=20, nullable=true)
     * @Assert\Length(max = 20)
     */
    protected $clientZipCode;

    /**
     * @var string|null
     *
     * @ORM\Column(name="client_company_registration_number", type="string", length=50, nullable=true)
     * @Assert\Length(max = 50)
     */
    protected $clientCompanyRegistrationNumber;

    /**
     * @var string|null
     *
     * @ORM\Column(name="client_company_tax_id", type="string", length=50, nullable=true)
     * @Assert\Length(max = 50)
     */
    protected $clientCompanyTaxId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="client_phone", type="string", length=50, nullable=true)
     * @Assert\Length(max = 50)
     */
    protected $clientPhone;

    /**
     * @var string|null
     *
     * @ORM\Column(name="client_email", type="string", length=320, nullable=true)
     * @Assert\Length(max = 320)
     * @Assert\Email(
     *     message="Client's email is not in valid format. Please update it in client edit form.",
     *     strict=true
     * )
     */
    protected $clientEmail;

    /**
     * @var string|null
     *
     * @ORM\Column(name="client_invoice_street1", type="string", length=250, nullable=true)
     * @Assert\Length(max = 250)
     */
    protected $clientInvoiceStreet1;

    /**
     * @var string|null
     *
     * @ORM\Column(name="client_invoice_street2", type="string", length=250, nullable=true)
     * @Assert\Length(max = 250)
     */
    protected $clientInvoiceStreet2;

    /**
     * @var string|null
     *
     * @ORM\Column(name="client_invoice_city", type="string", length=250, nullable=true)
     * @Assert\Length(max = 250)
     */
    protected $clientInvoiceCity;

    /**
     * @var Country|null
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Country", cascade={"persist"})
     * @ORM\JoinColumn(name="client_invoice_country_id", referencedColumnName="country_id", nullable=true)
     */
    protected $clientInvoiceCountry;

    /**
     * @var string|null
     *
     * @ORM\Column(name="client_invoice_zip_code", type="string", length=20, nullable=true)
     * @Assert\Length(max = 20)
     */
    protected $clientInvoiceZipCode;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="client_invoice_address_same_as_contact", type="boolean", nullable=true)
     */
    protected $clientInvoiceAddressSameAsContact;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="organization_id")
     */
    protected $organization;

    /**
     * @var string
     *
     * @ORM\Column(name="organization_name", type="string", length=255)
     * @Assert\Length(max = 255)
     * @Assert\NotBlank(
     *     message="Organization name is not filled. Make sure all required fields are filled in organization edit form."
     * )
     */
    protected $organizationName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="organization_registration_number", type="string", length=50, nullable=true)
     * @Assert\Length(max = 50)
     */
    protected $organizationRegistrationNumber;

    /**
     * @var string|null
     *
     * @ORM\Column(name="organization_tax_id", type="string", length=50, nullable=true)
     * @Assert\Length(max = 50)
     */
    protected $organizationTaxId;

    /**
     * @var string
     *
     * @ORM\Column(name="organization_email", type="string", length=320, options={"default":""})
     * @Assert\Length(max = 320)
     * @Assert\NotBlank(
     *     message="Organization email is not filled. Make sure all required fields are filled in organization edit form."
     * )
     * @Assert\Email(
     *     message="Organization email is not in valid format. Please update it in organization edit form.",
     *     strict=true
     * )
     */
    protected $organizationEmail;

    /**
     * @var string|null
     *
     * @ORM\Column(name="organization_phone", type="string", length=100, nullable=true)
     * @Assert\Length(max = 100)
     */
    protected $organizationPhone;

    /**
     * @var string|null
     *
     * @ORM\Column(name="organization_website", type="string", length=400, nullable=true)
     * @Assert\Length(max = 400)
     */
    protected $organizationWebsite;

    /**
     * @var string
     *
     * @ORM\Column(name="organization_street1", type="string", length=250, options={"default":""})
     * @Assert\Length(max = 250)
     * @Assert\NotBlank(
     *     message="Organization street is not filled. Make sure all required fields are filled in organization edit form."
     * )
     */
    protected $organizationStreet1;

    /**
     * @var string|null
     *
     * @ORM\Column(name="organization_street2", type="string", length=250, nullable=true)
     * @Assert\Length(max = 250)
     */
    protected $organizationStreet2;

    /**
     * @var string
     *
     * @ORM\Column(name="organization_city", type="string", length=250, options={"default":""})
     * @Assert\Length(max = 250)
     * @Assert\NotBlank(
     *     message="Organization city is not filled. Make sure all required fields are filled in organization edit form."
     * )
     */
    protected $organizationCity;

    /**
     * @var string
     *
     * @ORM\Column(name="organization_zip_code", type="string", length=20, options={"default":""})
     * @Assert\Length(max = 20)
     * @Assert\NotBlank(
     *     message="Organization ZIP code is not filled. Make sure all required fields are filled in organization edit form."
     * )
     */
    protected $organizationZipCode;

    /**
     * @var State|null
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\State")
     * @ORM\JoinColumn(name="organization_state_id", referencedColumnName="state_id", nullable=true)
     */
    protected $organizationState;

    /**
     * @var Country|null
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Country", cascade={"persist"})
     * @ORM\JoinColumn(name="organization_country_id", referencedColumnName="country_id", nullable=true)
     */
    protected $organizationCountry;

    /**
     * @var string|null
     *
     * @ORM\Column(name="organization_bank_account_field1", type="string", length=100, nullable=true)
     * @Assert\Length(max = 100)
     */
    protected $organizationBankAccountField1;

    /**
     * @var string|null
     *
     * @ORM\Column(name="organization_bank_account_field2", type="string", length=100, nullable=true)
     * @Assert\Length(max = 100)
     */
    protected $organizationBankAccountField2;

    /**
     * @var string|null
     *
     * @ORM\Column(name="organization_bank_account_name", type="string", length=50, nullable=true)
     * @Assert\Length(max = 50)
     */
    protected $organizationBankAccountName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="organization_logo_path", type="string", length=500, options={"default":""}, nullable=true)
     * @Assert\Length(max = 500)
     */
    protected $organizationLogoPath;

    /**
     * @var string|null
     *
     * @ORM\Column(name="organization_stamp_path", type="string", length=500, options={"default":""}, nullable=true)
     * @Assert\Length(max = 500)
     */
    protected $organizationStampPath;

    /**
     * @var State|null
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\State")
     * @ORM\JoinColumn(name="client_state_id", referencedColumnName="state_id", nullable=true)
     */
    protected $clientState;

    /**
     * @var Country|null
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Country", cascade={"persist"})
     * @ORM\JoinColumn(name="client_country_id", referencedColumnName="country_id", nullable=true)
     */
    protected $clientCountry;

    /**
     * @var State|null
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\State")
     * @ORM\JoinColumn(name="client_invoice_state_id", referencedColumnName="state_id", nullable=true)
     */
    protected $clientInvoiceState;

    /**
     * @var Currency
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Currency")
     * @ORM\JoinColumn(name="currency_id", referencedColumnName="currency_id", nullable=false)
     */
    protected $currency;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     * @Assert\Choice(choices=AppBundle\Entity\Financial\FinancialInterface::POSSIBLE_ITEM_ROUNDING, strict=true)
     */
    protected $itemRounding;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     * @Assert\Choice(choices=AppBundle\Entity\Financial\FinancialInterface::POSSIBLE_TAX_ROUNDING, strict=true)
     */
    protected $taxRounding;

    /**
     * @var int|null
     *
     * @ORM\Column(type="smallint")
     * @Assert\Choice(choices=AppBundle\Entity\Option::POSSIBLE_PRICING_MODES, strict=true)
     */
    protected $pricingMode;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $taxCoefficientPrecision;

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     */
    private $totalUntaxed;

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     */
    private $subtotal;

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     */
    private $totalDiscount;

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     */
    private $totalTaxAmount;

    /**
     * @var array
     *
     * @ORM\Column(type="json")
     */
    private $totalTaxes;

    /**
     * @var array
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    protected $clientAttributes = [];

    /**
     * Visible only to admin.
     *
     * @var string|null
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    protected $comment;

    /**
     * Visible to client (included on invoice/quote template).
     *
     * @var string|null
     *
     * @ORM\Column(name="notes", type="text", nullable=true)
     */
    protected $notes;

    /**
     * @var bool
     *
     * @ORM\Column(name="template_include_bank_account", type="boolean", options={"default":false})
     */
    protected $templateIncludeBankAccount = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="template_include_tax_information", type="boolean", options={"default":false})
     */
    protected $templateIncludeTaxInformation = false;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="email_sent_date", type="date", nullable=true)
     */
    protected $emailSentDate;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     */
    protected $totalRoundingDifference = 0;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $totalRoundingPrecision;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"default":PHP_ROUND_HALF_UP})
     * @Assert\Choice(choices={PHP_ROUND_HALF_UP}, strict=true)
     */
    protected $totalRoundingMode = PHP_ROUND_HALF_UP;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setTotal(float $total): void
    {
        $this->total = $total;
    }

    public function getTotal(): ?float
    {
        return $this->total;
    }

    public function getTotalUntaxed(): ?float
    {
        return $this->totalUntaxed;
    }

    public function setTotalUntaxed(float $totalUntaxed): void
    {
        $this->totalUntaxed = $totalUntaxed;
    }

    public function getSubtotal(): ?float
    {
        return $this->subtotal;
    }

    public function setSubtotal(float $subtotal): void
    {
        $this->subtotal = $subtotal;
    }

    public function getTotalDiscount(): ?float
    {
        return $this->totalDiscount;
    }

    public function setTotalDiscount(float $totalDiscount): void
    {
        $this->totalDiscount = $totalDiscount;
    }

    public function getTotalTaxAmount(): ?float
    {
        return $this->totalTaxAmount;
    }

    public function setTotalTaxAmount(float $totalTaxAmount): void
    {
        $this->totalTaxAmount = $totalTaxAmount;
    }

    public function getTotalTaxes(): ?array
    {
        return $this->totalTaxes;
    }

    public function setTotalTaxes(array $totalTaxes): void
    {
        $this->totalTaxes = $totalTaxes;
    }

    public function setDiscountType(int $discountType): void
    {
        $this->discountType = $discountType;
    }

    public function getDiscountType(): int
    {
        return $this->discountType;
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

    public function setCreatedDate(?\DateTime $createdDate): void
    {
        $this->createdDate = $createdDate;
    }

    public function getCreatedDate(): ?\DateTime
    {
        return $this->createdDate;
    }

    public function setClient(Client $client): void
    {
        $this->client = $client;

        if ($this instanceof Invoice) {
            $client->addInvoice($this);
        } elseif ($this instanceof Quote) {
            $client->addQuote($this);
        }
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setPdfPath(?string $pdfPath): void
    {
        $this->pdfPath = $pdfPath;
    }

    public function getPdfPath(): ?string
    {
        return $this->pdfPath;
    }

    public function setClientFirstName(?string $clientFirstName): void
    {
        $this->clientFirstName = $clientFirstName;
    }

    public function getClientFirstName(): ?string
    {
        return $this->clientFirstName;
    }

    public function setClientLastName(?string $clientLastName): void
    {
        $this->clientLastName = $clientLastName;
    }

    public function getClientLastName(): ?string
    {
        return $this->clientLastName;
    }

    public function setClientCompanyName(?string $clientCompanyName): void
    {
        $this->clientCompanyName = $clientCompanyName;
    }

    public function getClientCompanyName(): ?string
    {
        return $this->clientCompanyName;
    }

    public function setClientStreet1(?string $clientStreet1): void
    {
        $this->clientStreet1 = $clientStreet1;
    }

    public function getClientStreet1(): ?string
    {
        return $this->clientStreet1;
    }

    public function setClientStreet2(?string $clientStreet2): void
    {
        $this->clientStreet2 = $clientStreet2;
    }

    public function getClientStreet2(): ?string
    {
        return $this->clientStreet2;
    }

    public function setClientCity(?string $clientCity): void
    {
        $this->clientCity = $clientCity;
    }

    public function getClientCity(): ?string
    {
        return $this->clientCity;
    }

    public function setClientZipCode(?string $clientZipCode): void
    {
        $this->clientZipCode = $clientZipCode;
    }

    public function getClientZipCode(): ?string
    {
        return $this->clientZipCode;
    }

    public function setClientCompanyRegistrationNumber(?string $clientCompanyRegistrationNumber): void
    {
        $this->clientCompanyRegistrationNumber = $clientCompanyRegistrationNumber;
    }

    public function getClientCompanyRegistrationNumber(): ?string
    {
        return $this->clientCompanyRegistrationNumber;
    }

    public function setClientCompanyTaxId(?string $clientCompanyTaxId): void
    {
        $this->clientCompanyTaxId = $clientCompanyTaxId;
    }

    public function getClientCompanyTaxId(): ?string
    {
        return $this->clientCompanyTaxId;
    }

    public function setClientPhone(?string $clientPhone): void
    {
        $this->clientPhone = $clientPhone;
    }

    public function getClientPhone(): ?string
    {
        return $this->clientPhone;
    }

    public function setClientEmail(?string $clientEmail): void
    {
        $this->clientEmail = $clientEmail;
    }

    public function getClientEmail(): ?string
    {
        return $this->clientEmail;
    }

    public function setClientInvoiceStreet1(?string $clientInvoiceStreet1): void
    {
        $this->clientInvoiceStreet1 = $clientInvoiceStreet1;
    }

    public function getClientInvoiceStreet1(): ?string
    {
        return $this->clientInvoiceStreet1;
    }

    public function setClientInvoiceStreet2(?string $clientInvoiceStreet2): void
    {
        $this->clientInvoiceStreet2 = $clientInvoiceStreet2;
    }

    public function getClientInvoiceStreet2(): ?string
    {
        return $this->clientInvoiceStreet2;
    }

    public function setClientInvoiceCity(?string $clientInvoiceCity): void
    {
        $this->clientInvoiceCity = $clientInvoiceCity;
    }

    public function getClientInvoiceCity(): ?string
    {
        return $this->clientInvoiceCity;
    }

    public function setClientInvoiceZipCode(?string $clientInvoiceZipCode): void
    {
        $this->clientInvoiceZipCode = $clientInvoiceZipCode;
    }

    public function getClientInvoiceZipCode(): ?string
    {
        return $this->clientInvoiceZipCode;
    }

    public function setClientInvoiceAddressSameAsContact(?bool $clientInvoiceAddressSameAsContact): void
    {
        $this->clientInvoiceAddressSameAsContact = $clientInvoiceAddressSameAsContact;
    }

    public function getClientInvoiceAddressSameAsContact(): ?bool
    {
        return $this->clientInvoiceAddressSameAsContact;
    }

    public function setOrganizationName(?string $organizationName): void
    {
        $this->organizationName = $organizationName;
    }

    public function getOrganizationName(): ?string
    {
        return $this->organizationName;
    }

    public function setOrganizationRegistrationNumber(?string $organizationRegistrationNumber): void
    {
        $this->organizationRegistrationNumber = $organizationRegistrationNumber;
    }

    public function getOrganizationRegistrationNumber(): ?string
    {
        return $this->organizationRegistrationNumber;
    }

    public function setOrganizationTaxId(?string $organizationTaxId): void
    {
        $this->organizationTaxId = $organizationTaxId;
    }

    public function getOrganizationTaxId(): ?string
    {
        return $this->organizationTaxId;
    }

    public function setOrganizationEmail(?string $organizationEmail): void
    {
        $this->organizationEmail = $organizationEmail;
    }

    public function getOrganizationEmail(): ?string
    {
        return $this->organizationEmail;
    }

    public function setOrganizationPhone(?string $organizationPhone): void
    {
        $this->organizationPhone = $organizationPhone;
    }

    public function getOrganizationPhone(): ?string
    {
        return $this->organizationPhone;
    }

    public function setOrganizationWebsite(?string $organizationWebsite): void
    {
        $this->organizationWebsite = $organizationWebsite;
    }

    public function getOrganizationWebsite(): ?string
    {
        return $this->organizationWebsite;
    }

    public function setOrganizationStreet1(?string $organizationStreet1): void
    {
        $this->organizationStreet1 = $organizationStreet1;
    }

    public function getOrganizationStreet1(): ?string
    {
        return $this->organizationStreet1;
    }

    public function setOrganizationStreet2(?string $organizationStreet2): void
    {
        $this->organizationStreet2 = $organizationStreet2;
    }

    public function getOrganizationStreet2(): ?string
    {
        return $this->organizationStreet2;
    }

    public function setOrganizationCity(?string $organizationCity): void
    {
        $this->organizationCity = $organizationCity;
    }

    public function getOrganizationCity(): ?string
    {
        return $this->organizationCity;
    }

    public function setOrganizationZipCode(?string $organizationZipCode): void
    {
        $this->organizationZipCode = $organizationZipCode;
    }

    public function getOrganizationZipCode(): ?string
    {
        return $this->organizationZipCode;
    }

    public function setOrganizationState(?State $organizationState): void
    {
        $this->organizationState = $organizationState;
    }

    public function getOrganizationState(): ?State
    {
        return $this->organizationState;
    }

    public function setOrganizationCountry(?Country $organizationCountry): void
    {
        $this->organizationCountry = $organizationCountry;
    }

    public function getOrganizationCountry(): ?Country
    {
        return $this->organizationCountry;
    }

    public function setOrganizationBankAccountField1(?string $organizationBankAccountField1): void
    {
        $this->organizationBankAccountField1 = $organizationBankAccountField1;
    }

    public function getOrganizationBankAccountField1(): ?string
    {
        return $this->organizationBankAccountField1;
    }

    public function setOrganizationBankAccountField2(?string $organizationBankAccountField2): void
    {
        $this->organizationBankAccountField2 = $organizationBankAccountField2;
    }

    public function getOrganizationBankAccountField2(): ?string
    {
        return $this->organizationBankAccountField2;
    }

    public function setOrganizationBankAccountName(?string $organizationBankAccountName): void
    {
        $this->organizationBankAccountName = $organizationBankAccountName;
    }

    public function getOrganizationBankAccountName(): ?string
    {
        return $this->organizationBankAccountName;
    }

    public function setOrganizationLogoPath(?string $organizationLogoPath): void
    {
        $this->organizationLogoPath = $organizationLogoPath;
    }

    public function getOrganizationLogoPath(): ?string
    {
        return $this->organizationLogoPath;
    }

    public function setOrganizationStampPath(?string $organizationStampPath): void
    {
        $this->organizationStampPath = $organizationStampPath;
    }

    public function getOrganizationStampPath(): ?string
    {
        return $this->organizationStampPath;
    }

    public function setClientInvoiceCountry(?Country $clientInvoiceCountry): void
    {
        $this->clientInvoiceCountry = $clientInvoiceCountry;
    }

    public function getClientInvoiceCountry(): ?Country
    {
        return $this->clientInvoiceCountry;
    }

    public function setClientState(?State $clientState): void
    {
        $this->clientState = $clientState;
    }

    public function getClientState(): ?State
    {
        return $this->clientState;
    }

    public function setClientCountry(?Country $clientCountry): void
    {
        $this->clientCountry = $clientCountry;
    }

    public function getClientCountry(): ?Country
    {
        return $this->clientCountry;
    }

    public function setClientInvoiceState(?State $clientInvoiceState): void
    {
        $this->clientInvoiceState = $clientInvoiceState;
    }

    public function getClientInvoiceState(): ?State
    {
        return $this->clientInvoiceState;
    }

    public function setCurrency(Currency $currency): void
    {
        $this->currency = $currency;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setOrganization(Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function getItemRounding(): ?int
    {
        return $this->itemRounding;
    }

    public function setItemRounding(int $itemRounding): void
    {
        $this->itemRounding = $itemRounding;
    }

    public function getTaxRounding(): ?int
    {
        return $this->taxRounding;
    }

    public function setTaxRounding(int $taxRounding): void
    {
        $this->taxRounding = $taxRounding;
    }

    public function getPricingMode(): ?int
    {
        return $this->pricingMode;
    }

    public function setPricingMode(int $pricingMode): void
    {
        $this->pricingMode = $pricingMode;
    }

    public function getTaxCoefficientPrecision(): ?int
    {
        return $this->taxCoefficientPrecision;
    }

    public function setTaxCoefficientPrecision(?int $taxCoefficientPrecision): void
    {
        $this->taxCoefficientPrecision = $taxCoefficientPrecision;
    }

    public function getClientAttributes(): array
    {
        return $this->clientAttributes;
    }

    public function setClientAttributes(array $clientAttributes): void
    {
        $this->clientAttributes = $clientAttributes;
    }

    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getTemplateIncludeBankAccount(): bool
    {
        return $this->templateIncludeBankAccount;
    }

    public function setTemplateIncludeBankAccount(bool $templateIncludeBankAccount): void
    {
        $this->templateIncludeBankAccount = $templateIncludeBankAccount;
    }

    public function getTemplateIncludeTaxInformation(): bool
    {
        return $this->templateIncludeTaxInformation;
    }

    public function setTemplateIncludeTaxInformation(bool $templateIncludeTaxInformation): void
    {
        $this->templateIncludeTaxInformation = $templateIncludeTaxInformation;
    }

    public function getEmailSentDate(): ?\DateTime
    {
        return $this->emailSentDate;
    }

    public function setEmailSentDate(?\DateTime $emailSentDate): void
    {
        $this->emailSentDate = $emailSentDate;
    }

    public function getClientNameForView(): string
    {
        return $this->clientCompanyName ?: $this->clientFirstName . ' ' . $this->clientLastName;
    }

    public function getOrganizationBankAccountFieldsForView(): string
    {
        $account = [
            trim($this->organizationBankAccountField1 ?? ''),
            trim($this->organizationBankAccountField2 ?? ''),
        ];

        $account = array_filter($account);

        return implode(' / ', $account);
    }

    public function hasCustomTotalRounding(): bool
    {
        return $this->totalRoundingMode !== PHP_ROUND_HALF_UP
            || (
                $this->totalRoundingPrecision !== null
                && $this->totalRoundingPrecision !== $this->currency->getFractionDigits()
            );
    }

    public function getTotalRoundingDifference(): float
    {
        return $this->totalRoundingDifference;
    }

    public function setTotalRoundingDifference(float $totalRoundingDifference): void
    {
        $this->totalRoundingDifference = $totalRoundingDifference;
    }

    public function getTotalRoundingPrecision(): ?int
    {
        return $this->totalRoundingPrecision;
    }

    public function setTotalRoundingPrecision(?int $totalRoundingPrecision): void
    {
        $this->totalRoundingPrecision = $totalRoundingPrecision;
    }

    public function getTotalRoundingMode(): int
    {
        return $this->totalRoundingMode;
    }

    public function setTotalRoundingMode(int $totalRoundingMode): void
    {
        $this->totalRoundingMode = $totalRoundingMode;
    }
}
