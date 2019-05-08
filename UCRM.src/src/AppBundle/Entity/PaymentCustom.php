<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 */
class PaymentCustom implements PaymentDetailsInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name = "payment_custom_id", type = "integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy = "IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(length = 255)
     * @Assert\Length(max = 255)
     * @Assert\NotBlank()
     */
    protected $providerName;

    /**
     * @var string
     *
     * @ORM\Column(length = 255)
     * @Assert\Length(max = 255)
     * @Assert\NotBlank()
     */
    protected $providerPaymentId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime_utc")
     * @Assert\NotNull()
     */
    protected $providerPaymentTime;

    /**
     * @var float
     *
     * @ORM\Column(type = "float")
     * @Assert\NotNull()
     */
    protected $amount;

    /**
     * @var string
     *
     * @ORM\Column(length = 5, nullable=true)
     * @Assert\Length(max = 5)
     */
    protected $currency;

    public function getTransactionId(): ?string
    {
        return null;
    }

    public function getProviderId(): int
    {
        return PaymentProvider::ID_CUSTOM;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProviderName(): string
    {
        return $this->providerName;
    }

    public function setProviderName(string $providerName): PaymentCustom
    {
        $this->providerName = $providerName;

        return $this;
    }

    public function getProviderPaymentId(): string
    {
        return $this->providerPaymentId;
    }

    public function setProviderPaymentId(string $providerPaymentId): PaymentCustom
    {
        $this->providerPaymentId = $providerPaymentId;

        return $this;
    }

    public function getProviderPaymentTime(): \DateTime
    {
        return $this->providerPaymentTime;
    }

    public function setProviderPaymentTime(\DateTime $providerPaymentTime): PaymentCustom
    {
        $this->providerPaymentTime = $providerPaymentTime;

        return $this;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     */
    public function setAmount($amount): PaymentCustom
    {
        $this->amount = $amount;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): void
    {
        $this->currency = $currency;
    }
}
