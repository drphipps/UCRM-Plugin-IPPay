<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Financial;

use AppBundle\Entity\Service;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait FinancialItemServiceTrait
{
    /**
     * @var Service|null
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Service")
     * @ORM\JoinColumn(name="original_service_id", referencedColumnName="service_id")
     */
    protected $originalService;

    /**
     * @var int
     *
     * @ORM\Column(name="discount_type", type="integer", options={"unsigned":true, "default":0})
     */
    protected $discountType = FinancialInterface::DISCOUNT_NONE;

    /**
     * @var float|null
     *
     * @ORM\Column(name="discount_value", type="float", nullable=true)
     */
    protected $discountValue;

    /**
     * @var string|null
     *
     * @ORM\Column(name="discount_invoice_label", type="string", length=100, nullable=true)
     * @Assert\Length(max = 100)
     */
    protected $discountInvoiceLabel;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="discount_from", type="date", nullable=true)
     */
    protected $discountFrom;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="discount_to", type="date", nullable=true)
     */
    protected $discountTo;

    /**
     * @var float|null
     *
     * @ORM\Column(name="discount_quantity", type="float", nullable=true)
     */
    protected $discountQuantity;

    /**
     * @var float|null
     *
     * @ORM\Column(name="discount_price", type="float", nullable=true)
     */
    protected $discountPrice;

    /**
     * @var float|null
     *
     * @ORM\Column(name="discount_total", type="float", nullable=true)
     */
    protected $discountTotal;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="invoiced_from", type="date", nullable=true)
     */
    protected $invoicedFrom;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="invoiced_to", type="date", nullable=true)
     */
    protected $invoicedTo;

    public function setDiscountType(int $discountType): void
    {
        $this->discountType = $discountType;
    }

    public function getDiscountType(): int
    {
        return $this->discountType ?? FinancialInterface::DISCOUNT_NONE;
    }

    public function setDiscountValue(?float $discountValue): void
    {
        $this->discountValue = $discountValue;
    }

    public function getDiscountValue(): ?float
    {
        return $this->discountValue;
    }

    public function setDiscountInvoiceLabel(?string $discountInvoiceLabel): void
    {
        $this->discountInvoiceLabel = $discountInvoiceLabel;
    }

    public function getDiscountInvoiceLabel(): ?string
    {
        return $this->discountInvoiceLabel;
    }

    public function setDiscountFrom(?\DateTime $discountFrom): void
    {
        $this->discountFrom = $discountFrom;
    }

    public function getDiscountFrom(): ?\DateTime
    {
        return $this->discountFrom;
    }

    public function setDiscountTo(?\DateTime $discountTo): void
    {
        $this->discountTo = $discountTo;
    }

    public function getDiscountTo(): ?\DateTime
    {
        return $this->discountTo;
    }

    public function setDiscountQuantity(?float $discountQuantity): void
    {
        $this->discountQuantity = $discountQuantity;
    }

    public function getDiscountQuantity(): ?float
    {
        return $this->discountQuantity;
    }

    public function setDiscountPrice(?float $discountPrice): void
    {
        $this->discountPrice = $discountPrice;
    }

    public function getDiscountPrice(): ?float
    {
        return $this->discountPrice;
    }

    public function setDiscountTotal(?float $discountTotal): void
    {
        $this->discountTotal = $discountTotal;
    }

    public function getDiscountTotal(): ?float
    {
        return $this->discountTotal;
    }

    public function setInvoicedFrom(?\DateTime $invoicedFrom): void
    {
        $this->invoicedFrom = $invoicedFrom;
    }

    public function getInvoicedFrom(): ?\DateTime
    {
        return $this->invoicedFrom;
    }

    public function setInvoicedTo(?\DateTime $invoicedTo): void
    {
        $this->invoicedTo = $invoicedTo;
    }

    public function getInvoicedTo(): ?\DateTime
    {
        return $this->invoicedTo;
    }

    public function setService(?Service $service): void
    {
        $this->service = $service;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function getOriginalService(): ?Service
    {
        return $this->originalService;
    }

    public function setOriginalService(?Service $originalService): void
    {
        $this->originalService = $originalService;
    }
}
