<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Financial;

use AppBundle\Entity\Product;

interface FinancialItemProductInterface extends FinancialItemInterface
{
    public function setUnit(?string $unit): void;

    public function getUnit(): ?string;

    public function setProduct(?Product $product): void;

    public function getProduct(): ?Product;
}
