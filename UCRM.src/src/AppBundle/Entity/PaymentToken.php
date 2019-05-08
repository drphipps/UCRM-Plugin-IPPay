<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use AppBundle\Entity\Financial\Invoice;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PaymentTokenRepository")
 */
class PaymentToken
{
    /**
     * @var int
     *
     * @ORM\Column(name = "token_id", type = "integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy = "IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(length = 32)
     */
    protected $token;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type = "datetime_utc")
     */
    protected $created;

    /**
     * @var float|null
     *
     * @ORM\Column(type = "float", nullable = true)
     */
    protected $amount;

    /**
     * @var Invoice
     *
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Financial\Invoice", inversedBy="paymentToken")
     * @ORM\JoinColumn(referencedColumnName="invoice_id", nullable=false, onDelete="CASCADE")
     */
    protected $invoice;

    /**
     * @var PaymentStripePending|null
     *
     * @ORM\OneToOne(targetEntity="PaymentStripePending", mappedBy="paymentToken")
     */
    protected $paymentStripePending;

    public function generateToken(): void
    {
        $this->token = md5($this->invoice->getInvoiceNumber() . random_bytes(10));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getCreated(): \DateTime
    {
        return $this->created;
    }

    public function setCreated(\DateTime $created): void
    {
        $this->created = $created;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount = null): void
    {
        $this->amount = $amount;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(?Invoice $invoice): void
    {
        $this->invoice = $invoice;
    }

    public function getPaymentStripePending(): ?PaymentStripePending
    {
        return $this->paymentStripePending;
    }

    public function setPaymentStripePending(?PaymentStripePending $paymentPending): void
    {
        $this->paymentStripePending = $paymentPending;
    }
}
