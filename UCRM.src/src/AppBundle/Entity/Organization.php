<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use AppBundle\Entity\Financial\AccountStatementTemplate;
use AppBundle\Entity\Financial\InvoiceTemplate;
use AppBundle\Entity\Financial\ProformaInvoiceTemplate;
use AppBundle\Entity\Financial\QuoteTemplate;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\OrganizationRepository")
 * @ORM\Table(
 *     indexes={
 *         @ORM\Index(columns={"name"}),
 *     }
 * )
 * @Assert\GroupSequence({"IsUploadedFile", "Organization"})
 */
class Organization implements LoggableInterface, ParentLoggableInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="organization_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\Length(max = 255)
     * @Assert\NotBlank()
     */
    protected $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="registration_number", type="string", length=50, nullable=true)
     * @Assert\Length(max = 50)
     */
    protected $registrationNumber;

    /**
     * @var string|null
     *
     * @ORM\Column(name="tax_id", type="string", length=50, nullable=true)
     * @Assert\Length(max = 50)
     */
    protected $taxId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="phone", type="string", length=100, nullable=true)
     * @Assert\Length(max = 100)
     */
    protected $phone;

    /**
     * @ORM\Column(type="string", length=320, options={"default":""})
     * @Assert\Length(max = 320)
     * @Assert\NotBlank()
     * @Assert\Email(
     *     strict=true
     * )
     */
    protected $email;

    /**
     * @var string|null
     *
     * @ORM\Column(name="website", type="string", length=400, nullable=true)
     * @Assert\Length(max = 400)
     */
    protected $website;

    /**
     * @var string
     *
     * @ORM\Column(name="street1", type="string", length=250, options={"default":""})
     * @Assert\Length(max = 250)
     * @Assert\NotBlank()
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
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=250, options={"default":""})
     * @Assert\Length(max = 250)
     * @Assert\NotBlank()
     */
    protected $city;

    /**
     * @var State|null
     *
     * @ORM\ManyToOne(targetEntity="State")
     * @ORM\JoinColumn(name="state_id", referencedColumnName="state_id", nullable=true)
     */
    protected $state;

    /**
     * @var Country|null
     *
     * @ORM\ManyToOne(targetEntity="Country", cascade={"persist"})
     * @ORM\JoinColumn(name="country_id", referencedColumnName="country_id", nullable=true)
     */
    protected $country;

    /**
     * @var string
     *
     * @ORM\Column(name="zip_code", type="string", length=20, options={"default":""})
     * @Assert\Length(max = 20)
     * @Assert\NotBlank()
     */
    protected $zipCode;

    /**
     * @deprecated
     * @ORM\Column(name="invoice_day", type="integer", options={"default":1})
     */
    protected $invoiceDay = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="invoice_maturity_days", type="integer", options={"default":14})
     * @Assert\NotNull()
     * @Assert\LessThanOrEqual(value = 36500)
     * @Assert\GreaterThanOrEqual(value = 0)
     */
    protected $invoiceMaturityDays = 14;

    /**
     * @var string|null
     *
     * @ORM\Column(name="invoice_number_prefix", type="string", length=30, nullable=true)
     * @Assert\Length(max = 30)
     */
    protected $invoiceNumberPrefix;

    /**
     * @var int
     *
     * @ORM\Column(name="invoice_number_length", type="integer", options={"default":6})
     * @Assert\NotNull()
     * @Assert\LessThanOrEqual(value = 60)
     */
    protected $invoiceNumberLength = 6;

    /**
     * @var Collection|Client[]
     *
     * @ORM\OneToMany(targetEntity="Client", mappedBy="organization")
     */
    protected $clients;

    /**
     * @var OrganizationBankAccount|null
     *
     * @ORM\ManyToOne(targetEntity="OrganizationBankAccount", inversedBy="organizations")
     * @ORM\JoinColumn(name="account_id", referencedColumnName="account_id", nullable=true)
     */
    protected $bankAccount;

    /**
     * @var Currency
     *
     * @ORM\ManyToOne(targetEntity="Currency")
     * @ORM\JoinColumn(name="currency_id", referencedColumnName="currency_id")
     *
     * @Assert\NotNull()
     */
    protected $currency;

    /**
     * @var Collection|Tariff[]
     *
     * @ORM\OneToMany(targetEntity="Tariff", mappedBy="organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="organization_id")
     */
    protected $tariffs;

    /**
     * @var bool
     *
     * @ORM\Column(name="selected", type="boolean", nullable=true, options={"default":false})
     */
    protected $selected = false;

    /**
     * @var string|null
     *
     * @ORM\Column(name="logo", type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $logo;

    /**
     * @var string|null
     *
     * @ORM\Column(name="stamp", type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $stamp;

    /**
     * Type constraint for UploadedFile must be used to prevent file enumeration attack.
     *
     * @var UploadedFile|null
     *
     * @Assert\Type(
     *     type="\Symfony\Component\HttpFoundation\File\UploadedFile",
     *     groups={"IsUploadedFile"},
     *     message="Uploaded file is not valid."
     * )
     * @Assert\Image(
     *     mimeTypes = {"image/jpeg", "image/jpg", "image/gif", "image/png"},
     *     mimeTypesMessage = "Image must be in JPEG, PNG or GIF format.",
     *     maxSize = "2M"
     * )
     */
    private $fileLogo;

    /**
     * Type constraint for UploadedFile must be used to prevent file enumeration attack.
     *
     * @var UploadedFile|null
     *
     * @Assert\Type(
     *     type="\Symfony\Component\HttpFoundation\File\UploadedFile",
     *     groups={"IsUploadedFile"},
     *     message="Uploaded file is not valid."
     * )
     * @Assert\Image(
     *     mimeTypes = {"image/jpeg", "image/jpg", "image/gif", "image/png"},
     *     mimeTypesMessage = "Image must be in JPEG, PNG or GIF format.",
     *     maxSize = "2M"
     * )
     */
    private $fileStamp;

    /**
     * @var int
     *
     * @ORM\Column(name="invoice_init_number", type="integer", nullable=true, options={"default":1})
     * @Assert\LessThanOrEqual(value = 1000000000)
     */
    protected $invoiceInitNumber = 1;

    /**
     * @var string|null
     *
     * @ORM\Column(name="paypal_sandbox_client_id", type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $payPalSandboxClientId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="paypal_sandbox_client_secret", type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $payPalSandboxClientSecret;

    /**
     * @var string|null
     *
     * @ORM\Column(name="paypal_client_id", type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $payPalLiveClientId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="paypal_client_secret", type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $payPalLiveClientSecret;

    /**
     * @var string|null
     *
     * @ORM\Column(name="stripe_test_secret_key", type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $stripeTestSecretKey;

    /**
     * @var string|null
     *
     * @ORM\Column(name="stripe_test_publishable_key", type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $stripeTestPublishableKey;

    /**
     * @var string|null
     *
     * @ORM\Column(name="stripe_secret_key", type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $stripeLiveSecretKey;

    /**
     * @var string|null
     *
     * @ORM\Column(name="stripe_publishable_key", type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $stripeLivePublishableKey;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":false})
     */
    protected $stripeAchEnabled = false;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":false})
     */
    protected $stripeImportUnattachedPayments = false;

    /**
     * @var string|null
     *
     * @ORM\Column(name="anet_sandbox_login_id", type="string", length=20, nullable=true)
     * @Assert\Length(max = 20)
     */
    protected $anetSandboxLoginId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="anet_sandbox_transaction_key", type="string", length=16, nullable=true)
     * @Assert\Length(max = 16)
     */
    protected $anetSandboxTransactionKey;

    /**
     * @var string|null
     *
     * @ORM\Column(name="anet_sandbox_hash", type="string", length=20, nullable=true)
     * @Assert\Length(
     *     max = 20,
     *     maxMessage = "Hash can be up to 20 characters long."
     * )
     */
    protected $anetSandboxHash;

    /**
     * @var string|null
     * @var string|null
     *
     * @ORM\Column(length=128, nullable=true)
     * @Assert\Length(
     *     min = 128,
     *     max = 128,
     *     maxMessage = "Signature key must be 128 characters long."
     * )
     */
    protected $anetSandboxSignatureKey;

    /**
     * @var string|null
     *
     * @ORM\Column(name="anet_login_id", type="string", length=20, nullable=true)
     * @Assert\Length(max = 20)
     */
    protected $anetLiveLoginId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="anet_transaction_key", type="string", length=16, nullable=true)
     * @Assert\Length(max = 16)
     */
    protected $anetLiveTransactionKey;

    /**
     * @var string|null
     *
     * @ORM\Column(name="anet_hash", type="string", length=20, nullable=true)
     * @Assert\Length(
     *     max = 20,
     *     maxMessage = "Hash can be up to 20 characters long."
     * )
     */
    protected $anetLiveHash;

    /**
     * @var string|null
     *
     * @ORM\Column(length=128, nullable=true)
     * @Assert\Length(
     *     min = 128,
     *     max = 128,
     *     maxMessage = "Signature key must be 128 characters long."
     * )
     */
    protected $anetLiveSignatureKey;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $ipPaySandboxUrl;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $ipPayLiveUrl;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $ipPaySandboxTerminalId;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $ipPayLiveTerminalId;

    /**
     * @var Currency|null
     *
     * @ORM\ManyToOne(targetEntity="Currency")
     * @ORM\JoinColumn(referencedColumnName="currency_id", nullable=true)
     */
    protected $ipPaySandboxMerchantCurrency;

    /**
     * @var Currency|null
     *
     * @ORM\ManyToOne(targetEntity="Currency")
     * @ORM\JoinColumn(referencedColumnName="currency_id", nullable=true)
     */
    protected $ipPayLiveMerchantCurrency;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $mercadoPagoClientId;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $mercadoPagoClientSecret;

    /**
     * @var bool
     *
     * @ORM\Column(name="invoice_template_include_bank_account", type="boolean", options={"default":false})
     */
    protected $invoiceTemplateIncludeBankAccount = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="invoice_template_include_tax_information", type="boolean", options={"default":false})
     */
    protected $invoiceTemplateIncludeTaxInformation = false;

    /**
     * @var string|null
     *
     * @ORM\Column(name="invoice_template_default_notes", type="text", nullable=true)
     */
    protected $invoiceTemplateDefaultNotes;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":false})
     */
    protected $quoteTemplateIncludeBankAccount = false;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":false})
     */
    protected $quoteTemplateIncludeTaxInformation = false;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $quoteTemplateDefaultNotes;

    /**
     * @var InvoiceTemplate
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Financial\InvoiceTemplate")
     * @ORM\JoinColumn(nullable=false)
     *
     * @Assert\NotNull()
     */
    protected $invoiceTemplate;

    /**
     * @var ProformaInvoiceTemplate
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Financial\ProformaInvoiceTemplate")
     * @ORM\JoinColumn(nullable=false)
     *
     * @Assert\NotNull()
     */
    protected $proformaInvoiceTemplate;

    /**
     * @var QuoteTemplate
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Financial\QuoteTemplate")
     * @ORM\JoinColumn(nullable=false)
     *
     * @Assert\NotNull()
     */
    protected $quoteTemplate;

    /**
     * @var AccountStatementTemplate
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Financial\AccountStatementTemplate")
     * @ORM\JoinColumn(nullable=false)
     *
     * @Assert\NotNull()
     */
    protected $accountStatementTemplate;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":false})
     */
    protected $accountStatementTemplateIncludeBankAccount = false;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":false})
     */
    protected $accountStatementTemplateIncludeTaxInformation = false;

    /**
     * @var PaymentReceiptTemplate
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\PaymentReceiptTemplate")
     * @ORM\JoinColumn(nullable=false)
     *
     * @Assert\NotNull()
     */
    protected $paymentReceiptTemplate;

    /**
     * @var string|null
     *
     * @ORM\Column(length=30, nullable=true)
     * @Assert\Length(max = 30)
     */
    protected $quoteNumberPrefix;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"default":6})
     * @Assert\NotNull()
     * @Assert\LessThanOrEqual(value = 60)
     */
    protected $quoteNumberLength = 6;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true, options={"default":1})
     * @Assert\LessThanOrEqual(value = 1000000000)
     */
    protected $quoteInitNumber = 1;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Locale()
     */
    protected $locale;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":false})
     */
    protected $roundingTotalEnabled = false;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Expression(
     *     expression="not this.isRoundingTotalEnabled() or value !== null",
     *     message="This field is required."
     * )
     * @Assert\Range(
     *     min = -10,
     *     max = 10
     * )
     */
    protected $invoicedTotalRoundingPrecision;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"default":1})
     * @Assert\LessThanOrEqual(value = 2)
     */
    protected $invoicedTotalRoundingMode = 1;

    /**
     * @var string
     */
    private $fileName;

    /**
     * @var string|null
     *
     * @ORM\Column(length=30, nullable=true)
     * @Assert\Length(max = 30)
     */
    protected $proformaInvoiceNumberPrefix;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"default":6})
     * @Assert\NotNull()
     * @Assert\LessThanOrEqual(value = 60)
     */
    protected $proformaInvoiceNumberLength = 6;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true, options={"default":1})
     * @Assert\LessThanOrEqual(value = 1000000000)
     */
    protected $proformaInvoiceInitNumber = 1;

    /**
     * @var string|null
     *
     * @ORM\Column(length=30, nullable=true)
     * @Assert\Length(max = 30)
     */
    protected $receiptNumberPrefix;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"default":6})
     * @Assert\NotNull()
     * @Assert\LessThanOrEqual(value = 60)
     */
    protected $receiptNumberLength = 6;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true, options={"default":1})
     * @Assert\LessThanOrEqual(value = 1000000000)
     */
    protected $receiptInitNumber = 1;

    public function __construct()
    {
        $this->clients = new ArrayCollection();
        $this->tariffs = new ArrayCollection();
    }

    public function hasPaymentGateway(bool $sandbox): bool
    {
        return $this->hasPayPal($sandbox)
            || $this->hasStripe($sandbox)
            || $this->hasAuthorizeNet($sandbox)
            || $this->hasIpPay($sandbox)
            || $this->hasMercadoPago();
    }

    public function hasPayPal(bool $sandbox): bool
    {
        return null !== $this->getPayPalClientId($sandbox);
    }

    public function hasStripe(bool $sandbox): bool
    {
        return null !== $this->getStripePublishableKey($sandbox);
    }

    public function hasStripeAch(bool $sandbox): bool
    {
        return $this->hasStripe($sandbox) && $this->isStripeAchEnabled();
    }

    public function hasAuthorizeNet(bool $sandbox): bool
    {
        return null !== $this->getAnetLoginId($sandbox);
    }

    public function hasIpPay(bool $sandbox): bool
    {
        return null !== $this->getIpPayUrl($sandbox)
            && null !== $this->getIpPayTerminalId($sandbox)
            && null !== $this->getIpPayMerchantCurrency($sandbox);
    }

    public function hasMercadoPago(): bool
    {
        return null !== $this->getMercadoPagoClientId()
            && null !== $this->getMercadoPagoClientSecret();
    }

    public function hasPaymentProviderSupportingAutopay(bool $sandbox): bool
    {
        $includeServiceSelect = false;

        foreach (PaymentPlan::PROVIDER_SUPPORTED_AUTOPAY as $provider) {
            switch ($provider) {
                case PaymentPlan::PROVIDER_STRIPE:
                    $includeServiceSelect = $this->hasStripe($sandbox);
                    break;
                case PaymentPlan::PROVIDER_STRIPE_ACH:
                    $includeServiceSelect = $this->hasStripeAch($sandbox);
                    break;
            }

            if ($includeServiceSelect) {
                break;
            }
        }

        return $includeServiceSelect;
    }

    public function getPayPalClientId(bool $sandbox): ?string
    {
        return $sandbox ? $this->getPayPalSandboxClientId() : $this->getPayPalLiveClientId();
    }

    public function getPayPalClientSecret(bool $sandbox): ?string
    {
        return $sandbox ? $this->getPayPalSandboxClientSecret() : $this->getPayPalLiveClientSecret();
    }

    public function getStripeSecretKey(bool $sandbox): ?string
    {
        return $sandbox ? $this->getStripeTestSecretKey() : $this->getStripeLiveSecretKey();
    }

    public function getStripePublishableKey(bool $sandbox): ?string
    {
        return $sandbox ? $this->getStripeTestPublishableKey() : $this->getStripeLivePublishableKey();
    }

    public function getAnetLoginId(bool $sandbox): ?string
    {
        return $sandbox ? $this->getAnetSandboxLoginId() : $this->getAnetLiveLoginId();
    }

    public function getAnetTransactionKey(bool $sandbox): ?string
    {
        return $sandbox ? $this->getAnetSandboxTransactionKey() : $this->getAnetLiveTransactionKey();
    }

    public function getAnetHash(bool $sandbox): ?string
    {
        return $sandbox ? $this->getAnetSandboxHash() : $this->getAnetLiveHash();
    }

    public function getAnetSignatureKey(bool $sandbox): ?string
    {
        return $sandbox ? $this->getAnetSandboxSignatureKey() : $this->getAnetLiveSignatureKey();
    }

    public function getIpPayUrl(bool $sandbox): ?string
    {
        return $sandbox ? $this->getIpPaySandboxUrl() : $this->getIpPayLiveUrl();
    }

    public function getIpPayTerminalId(bool $sandbox): ?string
    {
        return $sandbox ? $this->getIpPaySandboxTerminalId() : $this->getIpPayLiveTerminalId();
    }

    public function getIpPayMerchantCurrency(bool $sandbox): ?Currency
    {
        return $sandbox ? $this->getIpPaySandboxMerchantCurrency() : $this->getIpPayLiveMerchantCurrency();
    }

    public function setFileName(string $fileName)
    {
        $this->fileName = $fileName;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setName(?string $name)
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function addClient(Client $client): void
    {
        $this->clients[] = $client;
    }

    public function removeClient(Client $client): void
    {
        $this->clients->removeElement($client);
    }

    /**
     * @return Collection|Client[]
     */
    public function getClients()
    {
        return $this->clients;
    }

    public function setRegistrationNumber(?string $registrationNumber): void
    {
        $this->registrationNumber = $registrationNumber;
    }

    public function getRegistrationNumber(): ?string
    {
        return $this->registrationNumber;
    }

    public function setTaxId(?string $taxId): void
    {
        $this->taxId = $taxId;
    }

    public function getTaxId(): ?string
    {
        return $this->taxId;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setWebsite(?string $website): void
    {
        $this->website = $website;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
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

    public function setState(?State $state): void
    {
        $this->state = $state;
    }

    public function getState(): ?State
    {
        return $this->state;
    }

    public function setCountry(?Country $country = null): void
    {
        $this->country = $country;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setInvoiceMaturityDays(int $invoiceMaturityDays): void
    {
        $this->invoiceMaturityDays = $invoiceMaturityDays;
    }

    public function getInvoiceMaturityDays(): int
    {
        return $this->invoiceMaturityDays;
    }

    public function setInvoiceNumberPrefix(?string $invoiceNumberPrefix): void
    {
        $this->invoiceNumberPrefix = $invoiceNumberPrefix;
    }

    public function getInvoiceNumberPrefix(): ?string
    {
        return $this->invoiceNumberPrefix;
    }

    public function setInvoiceNumberLength(int $invoiceNumberLength): void
    {
        $this->invoiceNumberLength = $invoiceNumberLength;
    }

    public function getInvoiceNumberLength(): int
    {
        return $this->invoiceNumberLength;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): void
    {
        $this->currency = $currency;
    }

    public function getBankAccount(): ?OrganizationBankAccount
    {
        return $this->bankAccount;
    }

    public function setBankAccount(?OrganizationBankAccount $bankAccount): void
    {
        $this->bankAccount = $bankAccount;
    }

    public function addTariff(Tariff $tariff): void
    {
        $this->tariffs[] = $tariff;
    }

    public function removeTariff(Tariff $tariff): void
    {
        $this->tariffs->removeElement($tariff);
    }

    /**
     * @return Collection|Tariff[]
     */
    public function getTariffs()
    {
        return $this->tariffs;
    }

    public function setSelected(bool $selected): void
    {
        $this->selected = $selected;
    }

    public function getSelected(): bool
    {
        return $this->selected;
    }

    public function setLogo(?string $logo): void
    {
        $this->logo = $logo;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setStamp(?string $stamp): void
    {
        $this->stamp = $stamp;
    }

    public function getStamp(): ?string
    {
        return $this->stamp;
    }

    public function setFileStamp(?UploadedFile $fileStamp = null): void
    {
        $this->fileStamp = $fileStamp;
    }

    public function getFileStamp(): ?UploadedFile
    {
        return $this->fileStamp;
    }

    public function setFileLogo(?UploadedFile $fileLogo = null): void
    {
        $this->fileLogo = $fileLogo;
    }

    public function getFileLogo(): ?UploadedFile
    {
        return $this->fileLogo;
    }

    public function getInvoiceInitNumber(): ?int
    {
        return $this->invoiceInitNumber;
    }

    public function setInvoiceInitNumber(?int $invoiceInitNumber): void
    {
        $this->invoiceInitNumber = $invoiceInitNumber;
    }

    public function getPayPalSandboxClientId(): ?string
    {
        return $this->payPalSandboxClientId;
    }

    public function setPayPalSandboxClientId(string $payPalSandboxClientId = null): void
    {
        $this->payPalSandboxClientId = $payPalSandboxClientId;
    }

    public function getPayPalSandboxClientSecret(): ?string
    {
        return $this->payPalSandboxClientSecret;
    }

    public function setPayPalSandboxClientSecret(?string $payPalSandboxClientSecret = null): void
    {
        $this->payPalSandboxClientSecret = $payPalSandboxClientSecret;
    }

    public function getPayPalLiveClientId(): ?string
    {
        return $this->payPalLiveClientId;
    }

    public function setPayPalLiveClientId(?string $payPalLiveClientId = null): void
    {
        $this->payPalLiveClientId = $payPalLiveClientId;
    }

    public function getPayPalLiveClientSecret(): ?string
    {
        return $this->payPalLiveClientSecret;
    }

    public function setPayPalLiveClientSecret(?string $payPalLiveClientSecret = null): void
    {
        $this->payPalLiveClientSecret = $payPalLiveClientSecret;
    }

    public function getStripeTestSecretKey(): ?string
    {
        return $this->stripeTestSecretKey;
    }

    public function setStripeTestSecretKey(?string $stripeTestSecretKey = null): void
    {
        $this->stripeTestSecretKey = $stripeTestSecretKey;
    }

    public function getStripeTestPublishableKey(): ?string
    {
        return $this->stripeTestPublishableKey;
    }

    public function setStripeTestPublishableKey(?string $stripeTestPublishableKey = null): void
    {
        $this->stripeTestPublishableKey = $stripeTestPublishableKey;
    }

    public function getStripeLiveSecretKey(): ?string
    {
        return $this->stripeLiveSecretKey;
    }

    public function setStripeLiveSecretKey(?string $stripeLiveSecretKey = null): void
    {
        $this->stripeLiveSecretKey = $stripeLiveSecretKey;
    }

    public function getStripeLivePublishableKey(): ?string
    {
        return $this->stripeLivePublishableKey;
    }

    public function setStripeLivePublishableKey(?string $stripeLivePublishableKey = null): void
    {
        $this->stripeLivePublishableKey = $stripeLivePublishableKey;
    }

    public function isStripeAchEnabled(): bool
    {
        return $this->stripeAchEnabled;
    }

    public function setStripeAchEnabled(bool $stripeAchEnabled): void
    {
        $this->stripeAchEnabled = $stripeAchEnabled;
    }

    public function isStripeImportUnattachedPayments(): bool
    {
        return $this->stripeImportUnattachedPayments;
    }

    public function setStripeImportUnattachedPayments(bool $stripeImportUnattachedPayments): void
    {
        $this->stripeImportUnattachedPayments = $stripeImportUnattachedPayments;
    }

    public function getAnetSandboxLoginId(): ?string
    {
        return $this->anetSandboxLoginId;
    }

    public function setAnetSandboxLoginId(?string $anetSandboxLoginId): void
    {
        $this->anetSandboxLoginId = $anetSandboxLoginId;
    }

    public function getAnetSandboxTransactionKey(): ?string
    {
        return $this->anetSandboxTransactionKey;
    }

    public function setAnetSandboxTransactionKey(?string $anetSandboxTransactionKey): void
    {
        $this->anetSandboxTransactionKey = $anetSandboxTransactionKey;
    }

    public function getAnetSandboxHash(): ?string
    {
        return $this->anetSandboxHash;
    }

    public function setAnetSandboxHash(?string $anetSandboxHash): void
    {
        $this->anetSandboxHash = $anetSandboxHash;
    }

    public function getAnetSandboxSignatureKey(): ?string
    {
        return $this->anetSandboxSignatureKey;
    }

    public function setAnetSandboxSignatureKey(?string $anetSandboxSignatureKey): void
    {
        $this->anetSandboxSignatureKey = $anetSandboxSignatureKey;
    }

    public function getAnetLiveLoginId(): ?string
    {
        return $this->anetLiveLoginId;
    }

    public function setAnetLiveLoginId(?string $anetLiveLoginId): void
    {
        $this->anetLiveLoginId = $anetLiveLoginId;
    }

    public function getAnetLiveTransactionKey(): ?string
    {
        return $this->anetLiveTransactionKey;
    }

    public function setAnetLiveTransactionKey(?string $anetLiveTransactionKey): void
    {
        $this->anetLiveTransactionKey = $anetLiveTransactionKey;
    }

    public function getAnetLiveHash(): ?string
    {
        return $this->anetLiveHash;
    }

    public function setAnetLiveHash(?string $anetLiveHash): void
    {
        $this->anetLiveHash = $anetLiveHash;
    }

    public function getAnetLiveSignatureKey(): ?string
    {
        return $this->anetLiveSignatureKey;
    }

    public function setAnetLiveSignatureKey(?string $anetLiveSignatureKey): void
    {
        $this->anetLiveSignatureKey = $anetLiveSignatureKey;
    }

    public function getIpPaySandboxUrl(): ?string
    {
        return $this->ipPaySandboxUrl;
    }

    public function setIpPaySandboxUrl(?string $ipPaySandboxUrl): void
    {
        $this->ipPaySandboxUrl = $ipPaySandboxUrl;
    }

    public function getIpPayLiveUrl(): ?string
    {
        return $this->ipPayLiveUrl;
    }

    public function setIpPayLiveUrl(?string $ipPayLiveUrl): void
    {
        $this->ipPayLiveUrl = $ipPayLiveUrl;
    }

    public function getIpPaySandboxTerminalId(): ?string
    {
        return $this->ipPaySandboxTerminalId;
    }

    public function setIpPaySandboxTerminalId(?string $ipPaySandboxTerminalId): void
    {
        $this->ipPaySandboxTerminalId = $ipPaySandboxTerminalId;
    }

    public function getIpPayLiveTerminalId(): ?string
    {
        return $this->ipPayLiveTerminalId;
    }

    public function setIpPayLiveTerminalId(?string $ipPayLiveTerminalId): void
    {
        $this->ipPayLiveTerminalId = $ipPayLiveTerminalId;
    }

    public function getIpPaySandboxMerchantCurrency(): ?Currency
    {
        return $this->ipPaySandboxMerchantCurrency;
    }

    public function setIpPaySandboxMerchantCurrency(?Currency $ipPaySandboxMerchantCurrency): void
    {
        $this->ipPaySandboxMerchantCurrency = $ipPaySandboxMerchantCurrency;
    }

    public function getIpPayLiveMerchantCurrency(): ?Currency
    {
        return $this->ipPayLiveMerchantCurrency;
    }

    public function setIpPayLiveMerchantCurrency(?Currency $ipPayLiveMerchantCurrency): void
    {
        $this->ipPayLiveMerchantCurrency = $ipPayLiveMerchantCurrency;
    }

    public function getMercadoPagoClientId(): ?string
    {
        return $this->mercadoPagoClientId;
    }

    public function setMercadoPagoClientId(?string $mercadoPagoClientId): void
    {
        $this->mercadoPagoClientId = $mercadoPagoClientId;
    }

    public function getMercadoPagoClientSecret(): ?string
    {
        return $this->mercadoPagoClientSecret;
    }

    public function setMercadoPagoClientSecret(?string $mercadoPagoClientSecret): void
    {
        $this->mercadoPagoClientSecret = $mercadoPagoClientSecret;
    }

    public function getInvoiceTemplateIncludeBankAccount(): bool
    {
        return $this->invoiceTemplateIncludeBankAccount;
    }

    public function setInvoiceTemplateIncludeBankAccount(bool $invoiceTemplateIncludeBankAccount): void
    {
        $this->invoiceTemplateIncludeBankAccount = $invoiceTemplateIncludeBankAccount;
    }

    public function getInvoiceTemplateIncludeTaxInformation(): bool
    {
        return $this->invoiceTemplateIncludeTaxInformation;
    }

    public function setInvoiceTemplateIncludeTaxInformation(bool $invoiceTemplateIncludeTaxInformation): void
    {
        $this->invoiceTemplateIncludeTaxInformation = $invoiceTemplateIncludeTaxInformation;
    }

    public function getInvoiceTemplateDefaultNotes(): ?string
    {
        return $this->invoiceTemplateDefaultNotes;
    }

    public function setInvoiceTemplateDefaultNotes(?string $invoiceTemplateDefaultNotes = null): void
    {
        $this->invoiceTemplateDefaultNotes = $invoiceTemplateDefaultNotes;
    }

    public function getQuoteTemplateDefaultNotes(): ?string
    {
        return $this->quoteTemplateDefaultNotes;
    }

    public function setQuoteTemplateDefaultNotes(?string $quoteTemplateDefaultNotes): void
    {
        $this->quoteTemplateDefaultNotes = $quoteTemplateDefaultNotes;
    }

    public function getQuoteTemplateIncludeBankAccount(): bool
    {
        return $this->quoteTemplateIncludeBankAccount;
    }

    public function setQuoteTemplateIncludeBankAccount(bool $quoteTemplateIncludeBankAccount): void
    {
        $this->quoteTemplateIncludeBankAccount = $quoteTemplateIncludeBankAccount;
    }

    public function getQuoteTemplateIncludeTaxInformation(): bool
    {
        return $this->quoteTemplateIncludeTaxInformation;
    }

    public function setQuoteTemplateIncludeTaxInformation(bool $quoteTemplateIncludeTaxInformation): void
    {
        $this->quoteTemplateIncludeTaxInformation = $quoteTemplateIncludeTaxInformation;
    }

    public function getInvoiceTemplate(): ?InvoiceTemplate
    {
        return $this->invoiceTemplate;
    }

    public function setInvoiceTemplate(?InvoiceTemplate $invoiceTemplate = null): void
    {
        $this->invoiceTemplate = $invoiceTemplate;
    }

    public function getProformaInvoiceTemplate(): ?ProformaInvoiceTemplate
    {
        return $this->proformaInvoiceTemplate;
    }

    public function setProformaInvoiceTemplate(?ProformaInvoiceTemplate $proformaInvoiceTemplate): void
    {
        $this->proformaInvoiceTemplate = $proformaInvoiceTemplate;
    }

    public function getQuoteTemplate(): ?QuoteTemplate
    {
        return $this->quoteTemplate;
    }

    public function setQuoteTemplate(?QuoteTemplate $quoteTemplate): void
    {
        $this->quoteTemplate = $quoteTemplate;
    }

    public function getAccountStatementTemplate(): ?AccountStatementTemplate
    {
        return $this->accountStatementTemplate;
    }

    public function setAccountStatementTemplate(?AccountStatementTemplate $accountStatementTemplate): void
    {
        $this->accountStatementTemplate = $accountStatementTemplate;
    }

    public function getAccountStatementTemplateIncludeBankAccount(): bool
    {
        return $this->accountStatementTemplateIncludeBankAccount;
    }

    public function setAccountStatementTemplateIncludeBankAccount(bool $include): void
    {
        $this->accountStatementTemplateIncludeBankAccount = $include;
    }

    public function getAccountStatementTemplateIncludeTaxInformation(): bool
    {
        return $this->accountStatementTemplateIncludeTaxInformation;
    }

    public function setAccountStatementTemplateIncludeTaxInformation(bool $include): void
    {
        $this->accountStatementTemplateIncludeTaxInformation = $include;
    }

    public function getPaymentReceiptTemplate(): ?PaymentReceiptTemplate
    {
        return $this->paymentReceiptTemplate;
    }

    public function setPaymentReceiptTemplate(?PaymentReceiptTemplate $paymentReceiptTemplate): void
    {
        $this->paymentReceiptTemplate = $paymentReceiptTemplate;
    }

    public function getQuoteNumberPrefix(): ?string
    {
        return $this->quoteNumberPrefix;
    }

    public function setQuoteNumberPrefix(?string $quoteNumberPrefix): void
    {
        $this->quoteNumberPrefix = $quoteNumberPrefix;
    }

    public function getQuoteNumberLength(): ?int
    {
        return $this->quoteNumberLength;
    }

    public function setQuoteNumberLength(?int $quoteNumberLength): void
    {
        $this->quoteNumberLength = $quoteNumberLength;
    }

    public function getQuoteInitNumber(): ?int
    {
        return $this->quoteInitNumber;
    }

    public function setQuoteInitNumber(?int $quoteInitNumber): void
    {
        $this->quoteInitNumber = $quoteInitNumber;
    }

    public function getProformaInvoiceNumberPrefix(): ?string
    {
        return $this->proformaInvoiceNumberPrefix;
    }

    public function setProformaInvoiceNumberPrefix(?string $proformaInvoiceNumberPrefix): void
    {
        $this->proformaInvoiceNumberPrefix = $proformaInvoiceNumberPrefix;
    }

    public function getProformaInvoiceNumberLength(): int
    {
        return $this->proformaInvoiceNumberLength;
    }

    public function setProformaInvoiceNumberLength(int $proformaInvoiceNumberLength): void
    {
        $this->proformaInvoiceNumberLength = $proformaInvoiceNumberLength;
    }

    public function getProformaInvoiceInitNumber(): ?int
    {
        return $this->proformaInvoiceInitNumber;
    }

    public function setProformaInvoiceInitNumber(?int $proformaInvoiceInitNumber): void
    {
        $this->proformaInvoiceInitNumber = $proformaInvoiceInitNumber;
    }

    public function getReceiptNumberPrefix(): ?string
    {
        return $this->receiptNumberPrefix;
    }

    public function setReceiptNumberPrefix(?string $receiptNumberPrefix): void
    {
        $this->receiptNumberPrefix = $receiptNumberPrefix;
    }

    public function getReceiptNumberLength(): int
    {
        return $this->receiptNumberLength;
    }

    public function setReceiptNumberLength(int $receiptNumberLength): void
    {
        $this->receiptNumberLength = $receiptNumberLength;
    }

    public function getReceiptInitNumber(): ?int
    {
        return $this->receiptInitNumber;
    }

    public function setReceiptInitNumber(?int $receiptInitNumber): void
    {
        $this->receiptInitNumber = $receiptInitNumber;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): void
    {
        $this->locale = $locale;
    }

    public function getInvoicedTotalRoundingPrecision(): ?int
    {
        return $this->invoicedTotalRoundingPrecision;
    }

    public function setInvoicedTotalRoundingPrecision(?int $invoicedTotalRoundingPrecision): void
    {
        $this->invoicedTotalRoundingPrecision = $invoicedTotalRoundingPrecision;
    }

    public function getInvoicedTotalRoundingMode(): int
    {
        return $this->invoicedTotalRoundingMode;
    }

    public function setInvoicedTotalRoundingMode(int $invoicedTotalRoundingMode): void
    {
        $this->invoicedTotalRoundingMode = $invoicedTotalRoundingMode;
    }

    public function isRoundingTotalEnabled(): bool
    {
        return $this->roundingTotalEnabled;
    }

    public function setRoundingTotalEnabled(bool $roundingTotalEnabled): void
    {
        $this->roundingTotalEnabled = $roundingTotalEnabled;
    }

    /**
     * @return array
     */
    public function getAddress(bool $includeStateAndCountry = true)
    {
        $address = [
            $this->getStreet1(),
            $this->getStreet2(),
            $this->getCity(),
            $this->getZipCode(),
            $includeStateAndCountry
                ? ($this->getState() ? $this->getState()->getName() : null)
                : null,
            $includeStateAndCountry
                ? ($this->getCountry() ? $this->getCountry()->getName() : null)
                : null,
        ];

        return array_filter($address);
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDeleteMessage()
    {
        $message['logMsg'] = [
            'message' => 'Organization %s deleted',
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
            'message' => 'Organization %s added',
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
            'password',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getLogClient()
    {
        return null;
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
        return null;
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
}
