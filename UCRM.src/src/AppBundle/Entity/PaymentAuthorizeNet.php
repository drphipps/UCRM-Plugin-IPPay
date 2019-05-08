<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="payment_anet")
 * @ORM\Entity()
 */
class PaymentAuthorizeNet implements PaymentDetailsInterface
{
    public const PROVIDER_NAME = 'AuthorizeNet';

    /**
     * @var int
     *
     * @ORM\Column(name="payment_anet_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="anet_id", type="string", length=255)
     * @Assert\Length(max = 255)
     * @Assert\NotBlank()
     */
    protected $anetId;

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
        return $this->getAnetId();
    }

    public function getProviderId(): int
    {
        return PaymentProvider::ID_AUTHORIZE_NET;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getAnetId()
    {
        return $this->anetId;
    }

    /**
     * @param string $anetId
     *
     * @return PaymentAuthorizeNet
     */
    public function setAnetId($anetId)
    {
        $this->anetId = $anetId;

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
     * @param Organization $organization
     *
     * @return PaymentAuthorizeNet
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;

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
     * @param Client $client
     *
     * @return PaymentAuthorizeNet
     */
    public function setClient($client)
    {
        $this->client = $client;

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
     *
     * @return PaymentAuthorizeNet
     */
    public function setAmount($amount)
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
