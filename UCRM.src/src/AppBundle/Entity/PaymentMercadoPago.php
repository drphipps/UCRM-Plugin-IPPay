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
class PaymentMercadoPago implements PaymentDetailsInterface
{
    public const PROVIDER_NAME = 'MercadoPago';

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(length=255, unique=true)
     * @Assert\Length(max = 255)
     */
    protected $mercadoPagoId;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Organization")
     * @ORM\JoinColumn(referencedColumnName="organization_id", nullable=false)
     */
    protected $organization;

    /**
     * @var Client
     *
     * @ORM\ManyToOne(targetEntity="Client")
     * @ORM\JoinColumn(referencedColumnName="client_id", nullable=false, onDelete="CASCADE")
     */
    protected $client;

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     */
    protected $amount;

    /**
     * @var string
     *
     * @ORM\Column(length=3)
     * @Assert\Length(max = 3)
     */
    protected $currency;

    public function getProviderName(): string
    {
        return self::PROVIDER_NAME;
    }

    public function getTransactionId(): string
    {
        return $this->getMercadoPagoId();
    }

    public function getProviderId(): int
    {
        return PaymentProvider::ID_MERCADO_PAGO;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMercadoPagoId(): string
    {
        return $this->mercadoPagoId;
    }

    public function setMercadoPagoId(string $mercadoPagoId): void
    {
        $this->mercadoPagoId = $mercadoPagoId;
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function setOrganization(Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
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
