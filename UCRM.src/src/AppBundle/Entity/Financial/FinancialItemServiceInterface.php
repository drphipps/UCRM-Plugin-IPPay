<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Financial;

use AppBundle\Entity\Service;

interface FinancialItemServiceInterface extends FinancialItemInterface
{
    public function setDiscountType(int $discountType): void;

    public function getDiscountType(): int;

    public function setDiscountValue(?float $discountValue): void;

    public function getDiscountValue(): ?float;

    public function setDiscountInvoiceLabel(?string $discountInvoiceLabel): void;

    public function getDiscountInvoiceLabel(): ?string;

    public function setDiscountFrom(?\DateTime $discountFrom): void;

    public function getDiscountFrom(): ?\DateTime;

    public function setDiscountTo(?\DateTime $discountTo): void;

    public function getDiscountTo(): ?\DateTime;

    public function setDiscountQuantity(?float $discountQuantity): void;

    public function getDiscountQuantity(): ?float;

    public function setDiscountPrice(?float $discountPrice): void;

    public function getDiscountPrice(): ?float;

    public function setDiscountTotal(?float $discountTotal): void;

    public function getDiscountTotal(): ?float;

    public function setInvoicedFrom(?\DateTime $invoicedFrom): void;

    public function getInvoicedFrom(): ?\DateTime;

    public function setInvoicedTo(?\DateTime $invoicedTo): void;

    public function getInvoicedTo(): ?\DateTime;

    public function setService(?Service $service): void;

    public function getService(): ?Service;

    public function getOriginalService(): ?Service;

    public function setOriginalService(?Service $originalService): void;
}
