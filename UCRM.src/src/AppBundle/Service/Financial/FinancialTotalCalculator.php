<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Financial;

use AppBundle\Entity\Financial\FinancialInterface;
use AppBundle\Entity\Financial\FinancialItemServiceInterface;
use AppBundle\Entity\Option;
use AppBundle\Entity\Tax;
use AppBundle\Service\Tax\TaxCalculator;

class FinancialTotalCalculator
{
    /**
     * @var TaxCalculator
     */
    private $taxCalculator;

    public function __construct(TaxCalculator $taxCalculator)
    {
        $this->taxCalculator = $taxCalculator;
    }

    /**
     * Computes total price with sub-calculations.
     */
    public function computeTotal(FinancialInterface $financial): FinancialTotalData
    {
        $totalData = new FinancialTotalData();

        $fractionDigits = $financial->getCurrency()->getFractionDigits();
        $items = $financial->getItems();

        // invoice supports only percentage discount
        $discountCoefficient = 1;
        if ($financial->getDiscountType() === FinancialInterface::DISCOUNT_PERCENTAGE) {
            $discountCoefficient = (100 - $financial->getDiscountValue()) / 100;
        }

        foreach ($items as $item) {
            $itemTotal = $item->getTotal();
            if ($item instanceof FinancialItemServiceInterface) {
                $itemTotal += $item->getDiscountTotal();
            }
            if ($financial->getItemRounding() === FinancialInterface::ITEM_ROUNDING_STANDARD) {
                $itemTotal = round($itemTotal, $fractionDigits);
            }
            $totalData->subtotal += $itemTotal;

            $taxLimit = $financial->getPricingMode() === Option::PRICING_MODE_WITH_TAXES ? 1 : 3;
            for ($i = 1; $i <= $taxLimit; ++$i) {
                /** @var Tax|null $tax */
                $tax = $item->{"getTax$i"}();
                $taxRate = $item->{"getTaxRate$i"}();

                if (! $taxRate) {
                    continue;
                }

                $itemTax = $this->taxCalculator->calculateTax(
                    $itemTotal,
                    $taxRate,
                    $financial->getPricingMode(),
                    $financial->getTaxCoefficientPrecision()
                );
                $itemTax *= $discountCoefficient;

                if ($financial->getTaxRounding() === FinancialInterface::TAX_ROUNDING_PER_ITEM) {
                    $itemTax = round($itemTax, $fractionDigits);
                }

                $taxKey = $tax ? sprintf('%s - %s%%', $tax->getName(), $taxRate) : $taxRate;

                if (array_key_exists($taxKey, $totalData->totalTaxes)) {
                    $totalData->totalTaxes[$taxKey] += $itemTax;
                } else {
                    $totalData->totalTaxes[$taxKey] = $itemTax;
                }

                if (array_key_exists($taxKey, $totalData->taxReport)) {
                    $totalData->taxReport[$taxKey]['total'] += $itemTax;
                } else {
                    $totalData->taxReport[$taxKey] = [
                        'tax' => $tax,
                        'taxRate' => $taxRate,
                        'currency' => $financial->getCurrency(),
                        'total' => $itemTax,
                    ];
                }
            }

            $totalData->totalDiscount += $itemTotal * (1 - $discountCoefficient);
        }

        $totalData->subtotal = round($totalData->subtotal, $fractionDigits);

        $totalData->totalTaxAmount = 0.0;
        foreach ($totalData->totalTaxes as $key => $taxPrice) {
            $totalData->totalTaxes[$key] = round($taxPrice, $fractionDigits);
            $totalData->totalTaxAmount += $totalData->totalTaxes[$key];
        }
        $totalData->totalTaxAmount = round($totalData->totalTaxAmount, $fractionDigits);

        // This is intentionally rounded before applying the minus sign. Although PHP rounds negative values using
        // half down algorithm rather than half up even when using the default rounding named PHP_ROUND_HALF_UP.
        $totalData->totalDiscount = round($totalData->totalDiscount, $fractionDigits);
        $totalData->totalDiscount *= -1;

        switch ($financial->getPricingMode()) {
            case Option::PRICING_MODE_WITHOUT_TAXES:
                $totalData->totalUntaxed = $totalData->subtotal + $totalData->totalDiscount;
                $totalData->total = $totalData->totalUntaxed + $totalData->totalTaxAmount;
                break;
            case Option::PRICING_MODE_WITH_TAXES:
                $totalData->total = $totalData->subtotal + $totalData->totalDiscount;
                $totalData->totalUntaxed = $totalData->subtotal - $totalData->totalTaxAmount;
                break;
            default:
                throw new \InvalidArgumentException('Unknown pricing mode.');
        }

        // Adding and subtracting can cause minor rounding errors as well.
        $totalData->totalUntaxed = round($totalData->totalUntaxed, $fractionDigits);

        $financial->setTotal($totalData->total);
        $financial->setSubtotal($totalData->subtotal);
        $financial->setTotalUntaxed($totalData->totalUntaxed);
        $financial->setTotalDiscount($totalData->totalDiscount);
        $financial->setTotalTaxAmount($totalData->totalTaxAmount);
        $financial->setTotalTaxes($totalData->totalTaxes);

        $this->roundTotal($financial);

        return $totalData;
    }

    private function roundTotal(FinancialInterface $financial): void
    {
        $organization = $financial->getOrganization();
        if (! $organization || $financial->getTotalRoundingPrecision() === null) {
            $financial->setTotal(
                round($financial->getTotal(), $financial->getCurrency()->getFractionDigits())
            );

            return;
        }

        $totalRounded = round(
            $financial->getTotal(),
            $financial->getTotalRoundingPrecision(),
            $financial->getTotalRoundingMode()
        );

        $financial->setTotalRoundingDifference(
            round(
                $totalRounded - $financial->getTotal(),
                $financial->getCurrency()->getFractionDigits()
            )
        );

        $financial->setTotal($totalRounded);
    }
}
