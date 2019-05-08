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
class PaymentStripePending
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"unsigned":true})
     * @Assert\NotBlank()
     * @Assert\Choice(choices=Payment::POSSIBLE_METHODS, strict=true)
     */
    protected $method;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime_utc")
     * @Assert\NotBlank()
     */
    protected $createdDate;

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     * @Assert\NotBlank()
     * @Assert\GreaterThan(0)
     */
    protected $amount;

    /**
     * @var Currency
     *
     * @ORM\ManyToOne(targetEntity="Currency")
     * @ORM\JoinColumn(referencedColumnName="currency_id")
     * @Assert\Expression(expression="not this.getClient() or value === this.getClient().getOrganization().getCurrency()", message="Payment currency does not match client's currency.")
     */
    protected $currency;

    /**
     * @var string
     *
     * @ORM\Column(name="payment_details_id", type="string")
     */
    protected $paymentDetailsId;

    /**
     * @var PaymentToken
     *
     * @ORM\OneToOne(targetEntity="PaymentToken", inversedBy="paymentStripePending", cascade={"persist"})
     * @ORM\JoinColumn(name="token_id", referencedColumnName="token_id", nullable=false, onDelete="CASCADE")
     */
    protected $paymentToken;

    /**
     * @var ClientBankAccount
     *
     * @ORM\ManyToOne(targetEntity="ClientBankAccount", inversedBy="paymentStripePendings")
     * @ORM\JoinColumn(referencedColumnName="client_bank_account_id")
     */
    protected $clientBankAccount;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getMethod(): int
    {
        return $this->method;
    }

    public function setMethod(int $method): void
    {
        $this->method = $method;
    }

    public function getCreatedDate(): \DateTime
    {
        return $this->createdDate;
    }

    public function setCreatedDate(\DateTime $createdDate): void
    {
        $this->createdDate = $createdDate;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function setCurrency(Currency $currency): void
    {
        $this->currency = $currency;
    }

    public function getPaymentDetailsId(): string
    {
        return $this->paymentDetailsId;
    }

    public function setPaymentDetailsId(string $paymentDetailsId): void
    {
        $this->paymentDetailsId = $paymentDetailsId;
    }

    public function getPaymentToken(): PaymentToken
    {
        return $this->paymentToken;
    }

    public function setPaymentToken(PaymentToken $paymentToken): void
    {
        $this->paymentToken = $paymentToken;
    }

    public function getClientBankAccount(): ClientBankAccount
    {
        return $this->clientBankAccount;
    }

    public function setClientBankAccount(ClientBankAccount $clientBankAccount): void
    {
        $this->clientBankAccount = $clientBankAccount;
    }
}
