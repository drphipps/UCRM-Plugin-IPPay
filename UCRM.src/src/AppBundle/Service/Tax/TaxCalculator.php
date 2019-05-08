<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Tax;

use AppBundle\Entity\Option;

class TaxCalculator
{
    public function calculateTax(
        float $itemTotal,
        float $taxRate,
        int $pricingMode,
        ?int $taxCoefficientPrecision
    ): float {
        switch ($pricingMode) {
            case Option::PRICING_MODE_WITHOUT_TAXES:
                return $itemTotal * $taxRate / 100;
            case Option::PRICING_MODE_WITH_TAXES:
                $coefficient = $taxRate / (100 + $taxRate);
                if ($taxCoefficientPrecision) {
                    $coefficient = round($coefficient, $taxCoefficientPrecision);
                }

                return $itemTotal * $coefficient;
            default:
                throw new \InvalidArgumentException('Unknown pricing mode.');
        }
    }
}
