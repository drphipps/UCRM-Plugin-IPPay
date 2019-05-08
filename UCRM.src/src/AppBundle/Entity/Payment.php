<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PaymentRepository")
 * @ORM\Table(
 *     indexes={
 *         @ORM\Index(columns={"created_date"}),
 *     },
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="receipt_number_unique", columns={"receipt_number", "organization_id"})
 *     }
 * )
 */
class Payment implements LoggableInterface, ParentLoggableInterface
{
    // If you add method here, add it also in AppBundle\Component\Import\CustomCsvImport::guessPaymentMethod()
    // and Refunds methods.
    const METHOD_CHECK = 1;
    const METHOD_CASH = 2;
    const METHOD_BANK_TRANSFER = 3;
    const METHOD_PAYPAL = 4;
    const METHOD_PAYPAL_CREDIT_CARD = 5;
    const METHOD_STRIPE = 6;
    const METHOD_STRIPE_SUBSCRIPTION = 7;
    const METHOD_PAYPAL_SUBSCRIPTION = 8;
    const METHOD_AUTHORIZE_NET = 9;
    const METHOD_AUTHORIZE_NET_SUBSCRIPTION = 10;
    const METHOD_COURTESY_CREDIT = 11;
    const METHOD_IPPAY = 12;
    const METHOD_IPPAY_SUBSCRIPTION = 13;
    const METHOD_MERCADO_PAGO = 14;
    const METHOD_MERCADO_PAGO_SUBSCRIPTION = 15;
    const METHOD_STRIPE_ACH = 16;
    const METHOD_STRIPE_SUBSCRIPTION_ACH = 17;
    const METHOD_CUSTOM = 99;

    const POSSIBLE_METHODS = [
        self::METHOD_CHECK,
        self::METHOD_CASH,
        self::METHOD_BANK_TRANSFER,
        self::METHOD_PAYPAL,
        self::METHOD_PAYPAL_CREDIT_CARD,
        self::METHOD_STRIPE,
        self::METHOD_STRIPE_SUBSCRIPTION,
        self::METHOD_PAYPAL_SUBSCRIPTION,
        self::METHOD_AUTHORIZE_NET,
        self::METHOD_AUTHORIZE_NET_SUBSCRIPTION,
        self::METHOD_COURTESY_CREDIT,
        self::METHOD_IPPAY,
        self::METHOD_IPPAY_SUBSCRIPTION,
        self::METHOD_MERCADO_PAGO,
        self::METHOD_MERCADO_PAGO_SUBSCRIPTION,
        self::METHOD_STRIPE_ACH,
        self::METHOD_STRIPE_SUBSCRIPTION_ACH,
        self::METHOD_CUSTOM,
    ];

    const METHOD_TYPE = [
        self::METHOD_CHECK => 'Check',
        self::METHOD_CASH => 'Cash',
        self::METHOD_BANK_TRANSFER => 'Bank transfer',
        self::METHOD_PAYPAL => 'PayPal',
        self::METHOD_PAYPAL_CREDIT_CARD => 'PayPal credit card',
        self::METHOD_STRIPE => 'Stripe credit card',
        self::METHOD_STRIPE_SUBSCRIPTION => 'Stripe subscription (credit card)',
        self::METHOD_PAYPAL_SUBSCRIPTION => 'PayPal subscription',
        self::METHOD_AUTHORIZE_NET => 'Authorize.Net credit card',
        self::METHOD_AUTHORIZE_NET_SUBSCRIPTION => 'Authorize.Net subscription',
        self::METHOD_COURTESY_CREDIT => 'Courtesy credit',
        self::METHOD_IPPAY => 'IPPay',
        self::METHOD_IPPAY_SUBSCRIPTION => 'IPPay subscription',
        self::METHOD_MERCADO_PAGO => 'MercadoPago',
        self::METHOD_MERCADO_PAGO_SUBSCRIPTION => 'MercadoPago subscription',
        self::METHOD_STRIPE_ACH => 'Stripe ACH',
        self::METHOD_STRIPE_SUBSCRIPTION_ACH => 'Stripe subscription (ACH)',
        self::METHOD_CUSTOM => 'Custom',
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="payment_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var int|null
     *
     * @ORM\Column(name="method", type="integer", options={"unsigned":true})
     * @Assert\NotBlank()
     * @Assert\Choice(choices=Payment::POSSIBLE_METHODS, strict=true)
     */
    protected $method;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_date", type="datetime_utc")
     * @Assert\NotBlank()
     */
    protected $createdDate;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float")
     * @Assert\NotBlank()
     * @Assert\GreaterThan(0)
     */
    protected $amount;

    /**
     * @var string|null
     *
     * @ORM\Column(name="note", type="text", nullable=true)
     */
    protected $note;

    /**
     * @var Collection|PaymentCover[]
     *
     * @ORM\OneToMany(targetEntity="PaymentCover", mappedBy="payment", cascade={"remove", "persist"})
     */
    protected $paymentCovers;

    /**
     * @var Client|null
     *
     * @ORM\ManyToOne(targetEntity="Client", inversedBy="payments")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="client_id", onDelete="CASCADE")
     */
    protected $client;

    /**
     * @var Credit|null
     *
     * @ORM\OneToOne(targetEntity="Credit", mappedBy="payment", cascade={"remove", "persist"})
     */
    protected $credit;

    /**
     * @var Currency|null
     *
     * @ORM\ManyToOne(targetEntity="Currency")
     * @ORM\JoinColumn(name="currency_id", referencedColumnName="currency_id", onDelete="SET NULL")
     */
    protected $currency;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="receipt_sent_date", type="datetime_utc", nullable=true)
     */
    protected $receiptSentDate;

    /**
     * @var PaymentProvider|null
     *
     * @ORM\ManyToOne(targetEntity="PaymentProvider")
     * @ORM\JoinColumn(referencedColumnName="provider_id")
     */
    protected $provider;

    /**
     * @var int|null
     *
     * @ORM\Column(name="payment_details_id", type="integer", nullable=true)
     */
    protected $paymentDetailsId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="check_number", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $checkNumber;

    /**
     * @var PaymentReceiptTemplate|null
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\PaymentReceiptTemplate")
     * @ORM\JoinColumn()
     */
    protected $paymentReceiptTemplate;

    /**
     * @var PaymentDetailsInterface|null
     *
     * @internal Used only for DummyPaymentFactory::createPayment()
     */
    protected $paymentDetails;

    /**
     * @var string|null
     *
     * @ORM\Column(length=500, nullable=true)
     * @Assert\Length(max = 500)
     */
    protected $pdfPath;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(referencedColumnName="user_id", onDelete="SET NULL")
     */
    protected $user;

    /**
     * @var string|null
     *
     * @ORM\Column(length=60, nullable=true)
     * @Assert\Length(max = 60)
     */
    protected $receiptNumber;

    /**
     * @var Organization|null
     *
     * @ORM\ManyToOne(targetEntity="Organization")
     * @ORM\JoinColumn(referencedColumnName="organization_id")
     */
    protected $organization;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":false})
     */
    protected $sendReceipt = false;

    public function __construct()
    {
        $this->paymentCovers = new ArrayCollection();
    }

    public function __clone()
    {
        $this->paymentCovers = clone $this->paymentCovers;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getMethod(): ?int
    {
        return $this->method;
    }

    public function setMethod(?int $method): Payment
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @throws \OutOfBoundsException
     */
    public function getMethodName(): string
    {
        if (! array_key_exists($this->method, self::METHOD_TYPE)) {
            throw new \OutOfBoundsException(sprintf('Name for method %d does not exist.', $this->method));
        }

        return self::METHOD_TYPE[$this->method];
    }

    public function getCreatedDate(): ?\DateTime
    {
        return $this->createdDate;
    }

    public function setCreatedDate(?\DateTime $createdDate): void
    {
        $this->createdDate = $createdDate;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(?float $amount): void
    {
        $this->amount = $amount;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note;
    }

    /**
     * @return Collection|PaymentCover[]
     */
    public function getPaymentCovers()
    {
        return $this->paymentCovers;
    }

    /**
     * @return Collection|PaymentCover[]
     */
    public function getPaymentCoversInvoices()
    {
        return $this->paymentCovers->matching(
            Criteria::create()->where(Criteria::expr()->neq('invoice', null))
        );
    }

    /**
     * @return Collection|PaymentCover[]
     */
    public function getPaymentCoversRefunds()
    {
        return $this->paymentCovers->matching(
            Criteria::create()->where(Criteria::expr()->neq('refund', null))
        );
    }

    public function addPaymentCover(PaymentCover $paymentCover): void
    {
        $this->paymentCovers[] = $paymentCover;
    }

    public function removePaymentCover(PaymentCover $paymentCover): void
    {
        $this->paymentCovers->removeElement($paymentCover);
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client = null): void
    {
        if ($client) {
            $client->addPayment($this);
        }

        if ($this->client && ! $client) {
            $this->client->removePayment($this);
        }

        $this->client = $client;
    }

    public function getCredit(): ?Credit
    {
        return $this->credit;
    }

    public function setCredit(Credit $credit = null): void
    {
        $this->credit = $credit;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency = null): void
    {
        $this->currency = $currency;
    }

    public function getReceiptSentDate(): ?\DateTime
    {
        return $this->receiptSentDate;
    }

    public function setReceiptSentDate(?\DateTime $receiptSentDate = null): void
    {
        $this->receiptSentDate = $receiptSentDate;
    }

    public function getCheckNumber(): ?string
    {
        return $this->checkNumber;
    }

    public function setCheckNumber(?string $checkNumber): void
    {
        $this->checkNumber = $checkNumber;
    }

    public function getProvider(): ?PaymentProvider
    {
        return $this->provider;
    }

    public function setProvider(?PaymentProvider $provider = null): void
    {
        $this->provider = $provider;
    }

    public function getPaymentDetailsId(): ?int
    {
        return $this->paymentDetailsId;
    }

    public function setPaymentDetailsId(?int $paymentDetailsId = null): void
    {
        $this->paymentDetailsId = $paymentDetailsId;
    }

    public function getPaymentReceiptTemplate(): ?PaymentReceiptTemplate
    {
        return $this->paymentReceiptTemplate;
    }

    public function setPaymentReceiptTemplate(?PaymentReceiptTemplate $paymentReceiptTemplate): void
    {
        $this->paymentReceiptTemplate = $paymentReceiptTemplate;
    }

    public function isMatched(): bool
    {
        return null !== $this->client && (null !== $this->credit || $this->paymentCovers->count() > 0);
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogInsertMessage(): array
    {
        $message['logMsg'] = [
            'message' => $this->getProvider()
                ? sprintf('Payment %%s from provider %s added', $this->getProvider()->getName())
                : 'Payment %s added',
            'replacements' => $this->getAmount(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogUpdateMessage(): array
    {
        $message['logMsg'] = [
            'id' => $this->getId(),
            'message' => $this->getAmount(),
            'entity' => self::class,
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDeleteMessage(): array
    {
        $message['logMsg'] = [
            'message' => 'Payment %s deleted',
            'replacements' => $this->getAmount(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogIgnoredColumns(): array
    {
        return [];
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
     * @internal
     */
    public function getPaymentDetails(): ?PaymentDetailsInterface
    {
        return $this->paymentDetails;
    }

    /**
     * @internal Used only for DummyPaymentFactory::createPayment()
     */
    public function setPaymentDetails(?PaymentDetailsInterface $paymentDetails): void
    {
        $this->paymentDetails = $paymentDetails;
    }

    public function getPdfPath(): ?string
    {
        return $this->pdfPath;
    }

    public function setPdfPath(?string $pdfPath): void
    {
        $this->pdfPath = $pdfPath;
    }

    public function getReceiptNumber(): ?string
    {
        return $this->receiptNumber;
    }

    public function setReceiptNumber(?string $receiptNumber): void
    {
        $this->receiptNumber = $receiptNumber;
    }

    /**
     * @internal Used only for DB uniq index
     */
    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    /**
     * @internal Used only for DB uniq index. Must be set to null when receiptNumber is set to null
     */
    public function setOrganization(?Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function isSendReceipt(): bool
    {
        return $this->sendReceipt;
    }

    public function setSendReceipt(bool $sendReceipt): void
    {
        $this->sendReceipt = $sendReceipt;
    }

    /**
     * @Assert\Callback()
     */
    public function validateAmountRefundable(ExecutionContextInterface $context): void
    {
        if (! $this->getClient() || ! $this->getCurrency()) {
            return;
        }

        $currencyCode = $this->getCurrency()->getCode();
        $clientCurrencyCode = $this->getClient()->getCurrencyCode();
        if ($currencyCode !== $clientCurrencyCode) {
            $context
                ->buildViolation(
                    'Payment currency (%paymentCurrency%) does not match client\'s currency (%clientCurrency%).'
                )
                ->setParameter('%paymentCurrency%', $currencyCode)
                ->setParameter('%clientCurrency%', $clientCurrencyCode)
                ->atPath('currency')
                ->addViolation();
        }
    }
}
