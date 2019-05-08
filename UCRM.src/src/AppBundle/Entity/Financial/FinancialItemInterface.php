<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Financial;

use AppBundle\Entity\Tax;

interface FinancialItemInterface
{
    public function setTax1(?Tax $tax1): void;

    public function getTax1(): ?Tax;

    public function setTax2(?Tax $tax2): void;

    public function getTax2(): ?Tax;

    public function setTax3(?Tax $tax3): void;

    public function getTax3(): ?Tax;

    public function getId(): ?int;

    public function setLabel(?string $label): void;

    public function getLabel(): ?string;

    public function setQuantity(?float $quantity): void;

    public function getQuantity(): ?float;

    public function setPrice(?float $price): void;

    public function getPrice(): ?float;

    public function setTotal(?float $total): void;

    public function getTotal(): float;

    public function setTaxable(bool $taxable): void;

    public function getTaxable(): bool;

    /**
     * @internal should be used only by FinancialItemInterface::setTax1
     */
    public function setTaxRate1(?float $taxRate1): void;

    public function getTaxRate1(): ?float;

    /**
     * @internal should be used only by FinancialItemInterface::setTax2
     */
    public function setTaxRate2(?float $taxRate2): void;

    public function getTaxRate2(): ?float;

    /**
     * @internal should be used only by FinancialItemInterface::setTax3
     */
    public function setTaxRate3(?float $taxRate3): void;

    public function getTaxRate3(): ?float;

    public function getFinancial(): ?FinancialInterface;

    public function getItemPosition(): ?int;

    public function setItemPosition(?int $itemPosition): void;
}
