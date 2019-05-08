<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Financial;

use AppBundle\Component\Validator\Constraints\FinancialItemPricingModeTax;
use AppBundle\Entity\Tax;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait FinancialItemTrait
{
    /**
     * @var int
     *
     * @ORM\Column(name="item_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=500)
     * @Assert\Length(max = 500)
     * @Assert\NotBlank()
     */
    protected $label;

    /**
     * @var float
     *
     * @ORM\Column(name="quantity", type="float")
     * @Assert\NotNull()
     */
    protected $quantity;

    /**
     * @var float
     *
     * @ORM\Column(name="price", type="float")
     * @Assert\NotBlank()
     * @Assert\NotNull()
     */
    protected $price;

    /**
     * @var float
     *
     * @ORM\Column(name="total", type="float")
     * @Assert\NotNull()
     */
    protected $total;

    /**
     * @var bool
     *
     * @ORM\Column(name="taxable", type="boolean", options={"default":false})
     */
    protected $taxable = false;

    /**
     * @var Tax|null
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Tax")
     * @ORM\JoinColumn(name="tax_id1", referencedColumnName="tax_id", nullable=true)
     * @FinancialItemPricingModeTax(groups={AppBundle\Entity\Financial\FinancialInterface::VALIDATION_GROUP_API})
     */
    protected $tax1;

    /**
     * @var Tax|null
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Tax")
     * @ORM\JoinColumn(name="tax_id2", referencedColumnName="tax_id", nullable=true)
     * @FinancialItemPricingModeTax(groups={AppBundle\Entity\Financial\FinancialInterface::VALIDATION_GROUP_API})
     */
    protected $tax2;

    /**
     * @var Tax|null
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Tax")
     * @ORM\JoinColumn(name="tax_id3", referencedColumnName="tax_id", nullable=true)
     * @FinancialItemPricingModeTax(groups={AppBundle\Entity\Financial\FinancialInterface::VALIDATION_GROUP_API})
     */
    protected $tax3;

    /**
     * @var float|null
     *
     * @ORM\Column(name="tax_rate1", type="float", nullable=true)
     */
    protected $taxRate1;

    /**
     * @var float|null
     *
     * @ORM\Column(name="tax_rate2", type="float", nullable=true)
     */
    protected $taxRate2;

    /**
     * @var float|null
     *
     * @ORM\Column(name="tax_rate3", type="float", nullable=true)
     */
    protected $taxRate3;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $itemPosition;

    public function setTax1(?Tax $tax1): void
    {
        $this->tax1 = $tax1;
        $this->setTaxRate1($this->tax1 ? $this->tax1->getRate() : null);
    }

    public function getTax1(): ?Tax
    {
        return $this->tax1;
    }

    public function setTax2(?Tax $tax2): void
    {
        $this->tax2 = $tax2;
        $this->setTaxRate2($this->tax2 ? $this->tax2->getRate() : null);
    }

    public function getTax2(): ?Tax
    {
        return $this->tax2;
    }

    public function setTax3(?Tax $tax3): void
    {
        $this->tax3 = $tax3;
        $this->setTaxRate3($this->tax3 ? $this->tax3->getRate() : null);
    }

    public function getTax3(): ?Tax
    {
        return $this->tax3;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setLabel(?string $label): void
    {
        $this->label = $label ?? '';
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setQuantity(?float $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function setPrice(?float $price): void
    {
        $this->price = $price;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setTotal(?float $total): void
    {
        $this->total = $total;
    }

    public function getTotal(): float
    {
        return $this->total ?? 0.0;
    }

    public function setTaxable(bool $taxable): void
    {
        $this->taxable = $taxable;
    }

    public function getTaxable(): bool
    {
        return $this->taxable;
    }

    /**
     * {@inheritdoc}
     */
    public function setTaxRate1(?float $taxRate1): void
    {
        $this->taxRate1 = $taxRate1;
    }

    public function getTaxRate1(): ?float
    {
        return $this->taxRate1;
    }

    /**
     * {@inheritdoc}
     */
    public function setTaxRate2(?float $taxRate2): void
    {
        $this->taxRate2 = $taxRate2;
    }

    public function getTaxRate2(): ?float
    {
        return $this->taxRate2;
    }

    /**
     * {@inheritdoc}
     */
    public function setTaxRate3(?float $taxRate3): void
    {
        $this->taxRate3 = $taxRate3;
    }

    public function getTaxRate3(): ?float
    {
        return $this->taxRate3;
    }

    public function getItemPosition(): ?int
    {
        return $this->itemPosition;
    }

    public function setItemPosition(?int $itemPosition): void
    {
        $this->itemPosition = $itemPosition;
    }

    public function calculateTotal(): void
    {
        $this->total = $this->price * $this->quantity;
    }
}
