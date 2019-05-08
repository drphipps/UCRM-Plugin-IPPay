<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\RefundRepository")
 * @ORM\Table(
 *     indexes={
 *         @ORM\Index(columns={"created_date"}),
 *     }
 * )
 */
class Refund implements LoggableInterface, ParentLoggableInterface
{
    public const METHOD_CHECK = 1;
    public const METHOD_CASH = 2;
    public const METHOD_BANK_TRANSFER = 3;
    public const METHOD_PAYPAL = 4;
    public const METHOD_PAYPAL_CREDIT_CARD = 5;
    public const METHOD_STRIPE = 6;
    public const METHOD_STRIPE_SUBSCRIPTION = 7;
    public const METHOD_PAYPAL_SUBSCRIPTION = 8;
    public const METHOD_AUTHORIZE_NET = 9;
    public const METHOD_AUTHORIZE_NET_SUBSCRIPTION = 10;
    public const METHOD_IPPAY = 12;
    public const METHOD_IPPAY_SUBSCRIPTION = 13;
    public const METHOD_MERCADO_PAGO = 14;
    public const METHOD_MERCADO_PAGO_SUBSCRIPTION = 15;
    public const METHOD_STRIPE_ACH = 16;
    public const METHOD_STRIPE_SUBSCRIPTION_ACH = 17;
    public const METHOD_CUSTOM = 99;

    public const POSSIBLE_METHODS = [
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
        self::METHOD_IPPAY,
        self::METHOD_IPPAY_SUBSCRIPTION,
        self::METHOD_MERCADO_PAGO,
        self::METHOD_MERCADO_PAGO_SUBSCRIPTION,
        self::METHOD_STRIPE_ACH,
        self::METHOD_STRIPE_SUBSCRIPTION_ACH,
        self::METHOD_CUSTOM,
    ];

    public const METHOD_TYPE = [
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
     * @ORM\Column(name="refund_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="method", type="integer", options={"unsigned":true})
     * @Assert\NotBlank()
     * @Assert\Choice(choices=Refund::POSSIBLE_METHODS, strict=true)
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
     * @var Client|null
     *
     * @ORM\ManyToOne(targetEntity="Client", inversedBy="refunds")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="client_id", nullable=true, onDelete="CASCADE")
     * @Assert\NotNull()
     */
    protected $client;

    /**
     * @var Currency|null
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Currency")
     * @ORM\JoinColumn(name="currency_id", referencedColumnName="currency_id", nullable=true, onDelete="SET NULL")
     * @Assert\Expression(
     *     expression="not value or (this.getClient() and value === this.getClient().getOrganization().getCurrency())",
     *     message="Refund currency does not match client's currency."
     * )
     */
    protected $currency;

    /**
     * @var Collection|PaymentCover[]
     *
     * @ORM\OneToMany(targetEntity="PaymentCover", mappedBy="refund", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="refund_id", referencedColumnName="refund_id")
     */
    protected $paymentCovers;

    public function __construct()
    {
        $this->paymentCovers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setMethod(?int $method): void
    {
        $this->method = $method;
    }

    public function getMethod(): ?int
    {
        return $this->method;
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

    public function setCreatedDate(?\DateTime $createdDate): void
    {
        $this->createdDate = $createdDate;
    }

    public function getCreatedDate(): ?\DateTime
    {
        return $this->createdDate;
    }

    public function setAmount(?float $amount): void
    {
        $this->amount = $amount;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setClient(?Client $client): void
    {
        $this->client = $client;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDeleteMessage()
    {
        $message['logMsg'] = [
            'message' => 'Refund %s deleted',
            'replacements' => $this->getAmount(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogInsertMessage()
    {
        $message['logMsg'] = [
            'message' => 'Refund %s added',
            'replacements' => $this->getAmount(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogIgnoredColumns()
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
     * {@inheritdoc}
     */
    public function getLogUpdateMessage()
    {
        $message['logMsg'] = [
            'id' => $this->getId(),
            'message' => $this->getAmount(),
            'entity' => self::class,
        ];

        return $message;
    }

    public function addPaymentCover(PaymentCover $paymentCover): void
    {
        if ($this->paymentCovers->contains($paymentCover)) {
            return;
        }

        $this->paymentCovers->add($paymentCover);
    }

    public function removePaymentCover(PaymentCover $paymentCover): void
    {
        if (! $this->paymentCovers->contains($paymentCover)) {
            return;
        }

        $this->paymentCovers->removeElement($paymentCover);
    }

    /**
     * @return Collection|PaymentCover[]
     */
    public function getPaymentCovers(): Collection
    {
        return $this->paymentCovers;
    }

    /**
     * @Assert\Callback()
     */
    public function validateAmountRefundable(ExecutionContextInterface $context): void
    {
        try {
            $amount = $this->getAmount();
            if ($amount === null) {
                return;
            }

            if (! ($client = $this->getClient())) {
                return;
            }

            $fractionDigits = $client->getOrganization()->getCurrency()->getFractionDigits();
            if (round($amount, $fractionDigits) > round($client->getAccountStandingsRefundableCredit(), $fractionDigits)) {
                throw new \InvalidArgumentException();
            }
        } catch (\InvalidArgumentException $exception) {
            $context->buildViolation('Refund amount is over client\'s credit.')
                ->atPath('amount')
                ->addViolation();
        }
    }
}
