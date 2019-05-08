<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="payment_paypal")
 * @ORM\Entity()
 */
class PaymentPayPal implements PaymentDetailsInterface
{
    public const TYPE_PAYMENT = 'payment';
    public const TYPE_SALE = 'sale';

    public const PROVIDER_NAME = 'PayPal';

    /**
     * @var int
     *
     * @ORM\Column(name="payment_paypal_id", type="integer")
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
     * @ORM\JoinColumn(name="client_id", referencedColumnName="client_id", nullable=false, onDelete="CASCADE")
     */
    protected $client;

    /**
     * @var string
     *
     * @ORM\Column(name="paypal_id", type="string", length=255, unique=true)
     * @Assert\Length(max = 255)
     * @Assert\NotBlank()
     */
    protected $payPalId;

    /**
     * @var string
     *
     * @ORM\Column(name="intent", type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $intent;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="string", length=255)
     * @Assert\Length(max = 255)
     * @Assert\NotBlank()
     */
    protected $state;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=20)
     * @Assert\Length(max = 20)
     * @Assert\NotBlank()
     */
    protected $type;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float")
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

    public function getProviderName(): string
    {
        return self::PROVIDER_NAME;
    }

    public function getTransactionId(): ?string
    {
        return $this->getPayPalId();
    }

    public function getProviderId(): int
    {
        return PaymentProvider::ID_PAYPAL;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param string $payPalId
     *
     * @return PaymentPayPal
     */
    public function setPayPalId($payPalId)
    {
        $this->payPalId = $payPalId;

        return $this;
    }

    /**
     * @return string
     */
    public function getPayPalId()
    {
        return $this->payPalId;
    }

    /**
     * @param string $intent
     *
     * @return PaymentPayPal
     */
    public function setIntent($intent)
    {
        $this->intent = $intent;

        return $this;
    }

    /**
     * @return string
     */
    public function getIntent()
    {
        return $this->intent;
    }

    /**
     * @param string $state
     *
     * @return PaymentPayPal
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param Organization $organization
     *
     * @return PaymentPayPal
     */
    public function setOrganization(Organization $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @return PaymentPayPal
     */
    public function setClient(Client $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param string $type
     *
     * @return PaymentPayPal
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param float $amount
     *
     * @return PaymentPayPal
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return float
     */
    public function getAmount()
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
}
