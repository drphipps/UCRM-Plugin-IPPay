<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use AppBundle\Entity\Financial\Invoice;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 */
class PaymentCover
{
    /**
     * @var int
     *
     * @ORM\Column(name="cover_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * Can be null. In that case, it's a "credit" and it doesn't cover any invoice.
     *
     * @var Invoice|null
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Financial\Invoice", inversedBy="paymentCovers", cascade={"persist"})
     * @ORM\JoinColumn(name="invoice_id", referencedColumnName="invoice_id", nullable=true)
     */
    protected $invoice;

    /**
     * @var Refund|null
     *
     * @ORM\ManyToOne(targetEntity="Refund", inversedBy="paymentCovers")
     * @ORM\JoinColumn(name="refund_id", referencedColumnName="refund_id", nullable=true)
     */
    protected $refund;

    /**
     * Can't be null. The source payment must be always traceable.
     *
     * @var Payment
     *
     * @Assert\NotNull()
     * @ORM\ManyToOne(targetEntity="Payment", inversedBy="paymentCovers")
     * @ORM\JoinColumn(name="payment_id", referencedColumnName="payment_id", nullable=false)
     */
    protected $payment;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float")
     */
    protected $amount;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setInvoice(?Invoice $invoice): void
    {
        $this->invoice = $invoice;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setPayment(Payment $payment): void
    {
        $this->payment = $payment;
    }

    public function getPayment(): Payment
    {
        return $this->payment;
    }

    public function setRefund(?Refund $refund): void
    {
        $this->refund = $refund;
    }

    public function getRefund(): ?Refund
    {
        return $this->refund;
    }
}
