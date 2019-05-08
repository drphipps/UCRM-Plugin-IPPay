<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 */
class PaymentStripe implements PaymentDetailsInterface
{
    public const PROVIDER_NAME = 'Stripe';

    /**
     * @var int
     *
     * @ORM\Column(name="payment_stripe_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="organization_id", nullable=false)
     */
    protected $organization;

    /**
     * @var Client
     *
     * @ORM\ManyToOne(targetEntity="Client")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="client_id", nullable=true, onDelete="CASCADE")
     */
    protected $client;

    /**
     * @var string
     *
     * @ORM\Column(name="request_id", type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $requestId;

    /**
     * @var string
     *
     * @ORM\Column(name="stripe_id", type="string", length=255, unique=true)
     * @Assert\Length(max = 255)
     * @Assert\NotBlank()
     */
    protected $stripeId;

    /**
     * @var string
     *
     * @ORM\Column(name="balance_transaction", type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     * @Assert\NotBlank()
     */
    protected $balanceTransaction;

    /**
     * @var string
     *
     * @ORM\Column(name="customer", type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $customer;

    /**
     * @var int
     *
     * @ORM\Column(name="amount", type="integer")
     */
    protected $amount;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=5)
     * @Assert\Length(max = 5)
     * @Assert\NotBlank()
     */
    protected $currency;

    /**
     * @var string
     *
     * @ORM\Column(name="source_card_id", type="string", length=255)
     * @Assert\Length(max = 255)
     * @Assert\NotBlank()
     */
    protected $sourceCardId;

    /**
     * @var string
     *
     * @ORM\Column(name="source_fingerprint", type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $sourceFingerprint;

    /**
     * @var string
     *
     * @ORM\Column(name="source_name", type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $sourceName;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=255)
     * @Assert\Length(max = 255)
     * @Assert\NotBlank()
     */
    protected $status;

    public function getProviderName(): string
    {
        return self::PROVIDER_NAME;
    }

    public function getTransactionId(): ?string
    {
        return $this->getStripeId();
    }

    public function getProviderId(): int
    {
        return PaymentProvider::ID_STRIPE;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setRequestId(?string $requestId): void
    {
        $this->requestId = $requestId;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function setStripeId(string $stripeId): void
    {
        $this->stripeId = $stripeId;
    }

    public function getStripeId(): string
    {
        return $this->stripeId;
    }

    public function setBalanceTransaction(?string $balanceTransaction): void
    {
        $this->balanceTransaction = $balanceTransaction;
    }

    public function getBalanceTransaction(): ?string
    {
        return $this->balanceTransaction;
    }

    public function setCustomer(?string $customer): void
    {
        $this->customer = $customer;
    }

    public function getCustomer(): ?string
    {
        return $this->customer;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setCurrency(?string $currency): void
    {
        $this->currency = $currency;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setSourceCardId(string $sourceCardId): void
    {
        $this->sourceCardId = $sourceCardId;
    }

    public function getSourceCardId(): string
    {
        return $this->sourceCardId;
    }

    public function setSourceFingerprint(?string $sourceFingerprint): void
    {
        $this->sourceFingerprint = $sourceFingerprint;
    }

    public function getSourceFingerprint(): ?string
    {
        return $this->sourceFingerprint;
    }

    public function setSourceName(?string $sourceName): void
    {
        $this->sourceName = $sourceName;
    }

    public function getSourceName(): ?string
    {
        return $this->sourceName;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setOrganization(Organization $organization = null): void
    {
        $this->organization = $organization;
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function setClient(?Client $client): void
    {
        $this->client = $client;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }
}
