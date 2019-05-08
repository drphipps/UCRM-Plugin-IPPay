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
 * @ORM\Table(name="payment_ippay")
 * @ORM\Entity()
 */
class PaymentIpPay implements PaymentDetailsInterface
{
    public const PROVIDER_NAME = 'IPpay';

    /**
     * @var int
     *
     * @ORM\Column(name="payment_paypal_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(length=18)
     * @Assert\Length(max=18)
     */
    protected $transactionId;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=5)
     * @Assert\Length(max=5)
     * @Assert\NotBlank()
     */
    protected $currency;

    public function getProviderName(): string
    {
        return self::PROVIDER_NAME;
    }

    public function getProviderId(): int
    {
        return PaymentProvider::ID_IPPAY;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function setTransactionId(string $transactionId): void
    {
        $this->transactionId = $transactionId;
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
