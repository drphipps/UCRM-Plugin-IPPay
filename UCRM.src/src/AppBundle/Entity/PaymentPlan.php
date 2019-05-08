<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use AppBundle\Component\Validator\Constraints as CustomAssert;
use AppBundle\Util\Strings;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\GroupSequenceProviderInterface;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PaymentPlanRepository")
 * @Assert\GroupSequenceProvider()
 */
class PaymentPlan implements LoggableInterface, ParentLoggableInterface, GroupSequenceProviderInterface
{
    public const PROVIDER_STRIPE = 'stripe';
    public const PROVIDER_STRIPE_ACH = 'Stripe ACH';
    public const PROVIDER_PAYPAL = 'paypal';
    public const PROVIDER_ANET = 'authorize.net';
    public const PROVIDER_IPPAY = 'ippay';
    public const PROVIDER_MERCADO_PAGO = 'MercadoPago';

    public const PROVIDERS = [
        self::PROVIDER_STRIPE,
        self::PROVIDER_STRIPE_ACH,
        self::PROVIDER_PAYPAL,
        self::PROVIDER_ANET,
        self::PROVIDER_IPPAY,
        self::PROVIDER_MERCADO_PAGO,
    ];

    public const PROVIDER_NAMES = [
        self::PROVIDER_STRIPE => 'Stripe',
        self::PROVIDER_STRIPE_ACH => 'Stripe ACH',
        self::PROVIDER_PAYPAL => 'PayPal',
        self::PROVIDER_ANET => 'Authorize.Net',
        self::PROVIDER_IPPAY => 'IPpay',
        self::PROVIDER_MERCADO_PAGO => 'Mercado Pago',
    ];

    // When new amount change provider is implemented, extend Organization::hasPaymentProviderSupportingAutopay()
    public const PROVIDER_SUPPORTED_AUTOPAY = [
        self::PROVIDER_STRIPE,
        self::PROVIDER_STRIPE_ACH,
    ];

    public const STATUS_CREATED = 'created';
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_ERROR = 'error';

    public const STATUSES = [
        self::STATUS_CREATED,
        self::STATUS_PENDING,
        self::STATUS_ACTIVE,
        self::STATUS_CANCELLED,
        self::STATUS_PAUSED,
        self::STATUS_ERROR,
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="payment_plan_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="provider", type="string", length=20, nullable=true)
     * @Assert\Choice(choices=PaymentPlan::PROVIDERS, strict=true)
     */
    protected $provider;

    /**
     * @var string|null
     *
     * @ORM\Column(name="provider_plan_id", type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $providerPlanId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="provider_subscription_id", type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $providerSubscriptionId;

    /**
     * @var Client
     *
     * @ORM\ManyToOne(targetEntity="Client", inversedBy="paymentPlans")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="client_id", nullable=false, onDelete="CASCADE")
     * @Assert\NotNull()
     */
    protected $client;

    /**
     * @var Currency
     *
     * @ORM\ManyToOne(targetEntity="Currency")
     * @ORM\JoinColumn(name="currency_id", referencedColumnName="currency_id", nullable=false)
     * @Assert\NotNull()
     * @Assert\Expression("this.getClient() && value === this.getClient().getOrganization().getCurrency()", message="Currency is not the same as the client's currency.")
     */
    protected $currency;

    /**
     * @var int amount in smallest unit
     *
     * @ORM\Column(type="integer")
     * @Assert\GreaterThan(value="0", groups={"PaymentPlan_NoService"})
     * @Assert\NotNull(groups={"PaymentPlan_NoService"})
     */
    protected $amountInSmallestUnit;

    /**
     * @var int
     *
     * @ORM\Column(name="period", type="integer", length=3)
     * @Assert\NotNull(groups={"PaymentPlan_NoService"})
     * @Assert\Choice(choices=TariffPeriod::PERIODS, strict=true, groups={"PaymentPlan_NoService"})
     */
    protected $period;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_date", type="datetime_utc")
     */
    protected $createdDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="canceled_date", type="datetime_utc", nullable=true)
     */
    protected $canceledDate;

    /**
     * This column must be nullable because of old payment plans (prior to 2.2.3), however new payment plans
     * should be created only with NOT null start date.
     *
     * @var \DateTime
     *
     * @ORM\Column(name="start_date", type="datetime_utc", nullable=true)
     * @Assert\NotNull()
     * @Assert\GreaterThanOrEqual(value="today midnight", message="Start date should be at least today.")
     */
    protected $startDate;

    /**
     * Date of the next payment. Currently used only by IpPay subscriptions.
     *
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime_utc", nullable=true)
     */
    protected $nextPaymentDate;

    /**
     * Number of failures. Currently used only by IpPay subscriptions.
     *
     * @var int
     *
     * @ORM\Column(type="smallint", options={"default": 0})
     * @Assert\NotNull()
     */
    protected $failures = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=50)
     * @Assert\NotNull()
     * @Assert\Choice(choices=PaymentPlan::STATUSES, strict=true)
     */
    protected $status;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": false})
     * @Assert\NotNull()
     */
    protected $cancellationFailed = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean", options={"default":false})
     */
    protected $active = false;

    /**
     * @var Service|null
     *
     * @ORM\ManyToOne(targetEntity="Service", inversedBy="paymentPlans")
     * @ORM\JoinColumn(referencedColumnName="service_id", nullable=true, onDelete="SET NULL")
     * @CustomAssert\PaymentPlanService()
     */
    protected $service;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":false})
     */
    protected $linked = false;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint", options={"default":100})
     */
    protected $smallestUnitMultiplier = 100;

    public function __construct()
    {
        $this->createdDate = new \DateTime();
        $this->startDate = new \DateTime();
        $this->status = self::STATUS_CREATED;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setProvider(?string $provider): void
    {
        $this->provider = $provider;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProviderPlanId(?string $providerPlanId): void
    {
        $this->providerPlanId = $providerPlanId;
    }

    public function getProviderPlanId(): ?string
    {
        return $this->providerPlanId;
    }

    /**
     * Generate provider plan ID, disabled for PayPal - it provides ID automatically.
     */
    public function generateProviderPlanId(): void
    {
        if ($this->provider !== self::PROVIDER_PAYPAL) {
            $this->providerPlanId = sprintf(
                'ucrm_%s_p%s_%s',
                Strings::slugify($this->provider),
                $this->id,
                $this->createdDate->getTimestamp()
            );
        }
    }

    public function setAmountInSmallestUnit(?int $amountInSmallestUnit): void
    {
        $this->amountInSmallestUnit = $amountInSmallestUnit;
    }

    public function getAmountInSmallestUnit(): ?int
    {
        return $this->amountInSmallestUnit;
    }

    public function setPeriod(?int $period): void
    {
        $this->period = $period;
    }

    public function getPeriod(): ?int
    {
        return $this->period;
    }

    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function setCurrency(Currency $currency): void
    {
        $this->currency = $currency;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function setProviderSubscriptionId(?string $providerSubscriptionId): void
    {
        $this->providerSubscriptionId = $providerSubscriptionId;
    }

    public function getProviderSubscriptionId(): ?string
    {
        return $this->providerSubscriptionId;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setCreatedDate(\DateTime $createdDate): void
    {
        $this->createdDate = $createdDate;
    }

    public function getCreatedDate(): \DateTime
    {
        return $this->createdDate;
    }

    public function setCanceledDate(?\DateTime $canceledDate): void
    {
        $this->canceledDate = $canceledDate;
    }

    public function getCanceledDate(): ?\DateTime
    {
        return $this->canceledDate;
    }

    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    public function getFutureStartDate(): ?\DateTime
    {
        if (
            ! $this->startDate
            || ! in_array(
                $this->status,
                [
                    self::STATUS_ACTIVE,
                    self::STATUS_PENDING,
                ]
            )
        ) {
            return null;
        }

        $now = new \DateTime('today midnight');
        $startDate = (clone $this->startDate)->modify('midnight');

        return $startDate > $now ? $startDate : null;
    }

    public function setStartDate(?\DateTime $startDate = null): void
    {
        $this->startDate = $startDate;
    }

    public function getNextPaymentDate(): ?\DateTime
    {
        return $this->nextPaymentDate;
    }

    public function setNextPaymentDate(?\DateTime $nextPaymentDate)
    {
        $this->nextPaymentDate = $nextPaymentDate;
    }

    public function getFailures(): int
    {
        return $this->failures;
    }

    public function setFailures(int $failures): void
    {
        $this->failures = $failures;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function isCancellationFailed(): bool
    {
        return $this->cancellationFailed;
    }

    public function setCancellationFailed(bool $cancellationFailed): void
    {
        $this->cancellationFailed = $cancellationFailed;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): void
    {
        $this->service = $service;
    }

    public function isLinked(): bool
    {
        return $this->linked;
    }

    public function setLinked(bool $autopay): void
    {
        $this->linked = $autopay;
    }

    public function getSmallestUnitMultiplier(): int
    {
        return $this->smallestUnitMultiplier;
    }

    public function setSmallestUnitMultiplier(int $smallestUnitMultiplier): void
    {
        $this->smallestUnitMultiplier = $smallestUnitMultiplier;
    }

    public function getLogInsertMessage(): array
    {
        $message['logMsg'] = [
            'message' => 'Payment plan %s created',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    public function getLogDeleteMessage(): array
    {
        $message['logMsg'] = [
            'message' => 'Payment plan %s removed',
            'replacements' => sprintf('%s (%s)', $this->getName(), $this->getProvider()),
        ];

        return $message;
    }

    public function getLogIgnoredColumns(): array
    {
        return [];
    }

    public function getLogClient(): ?Client
    {
        return $this->getClient();
    }

    public function getLogSite(): ?Site
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

    public function getLogUpdateMessage(): array
    {
        $message = [];
        $message['logMsg'] = [
            'id' => $this->getId(),
            'message' => $this->getName(),
            'entity' => self::class,
        ];

        return $message;
    }

    public function getGroupSequence(): array
    {
        $groups = ['PaymentPlan'];

        if (! $this->getService()) {
            $groups[] = 'PaymentPlan_NoService';
        }

        return $groups;
    }
}
