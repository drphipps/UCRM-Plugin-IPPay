<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service;

use AppBundle\Entity\Financial\FinancialInterface;
use AppBundle\Entity\Option;
use AppBundle\Entity\Service;
use AppBundle\Entity\Tax;
use AppBundle\Service\Tax\TaxCalculator;
use AppBundle\Util\Invoicing;

class ServiceCalculations
{
    /**
     * @var Options
     */
    private $options;

    /**
     * @var TaxCalculator
     */
    private $taxCalculator;

    public function __construct(Options $options, TaxCalculator $taxCalculator)
    {
        $this->options = $options;
        $this->taxCalculator = $taxCalculator;
    }

    /**
     * Get calculated total price for invoiced period (surcharges and discount included).
     */
    public function getTotalPrice(
        Service $service,
        ?\DateTime $invoicedFrom = null,
        ?\DateTime $invoicedTo = null
    ): float {
        [$invoicedFrom, $invoicedTo] = $this->getPeriod($service, $invoicedFrom, $invoicedTo);
        $price = $this->getBasePrice($service, $invoicedFrom, $invoicedTo);
        $addTaxes = $this->options->get(Option::PRICING_MODE) === Option::PRICING_MODE_WITHOUT_TAXES;
        $fractionDigits = $service->getClient()->getOrganization()->getCurrency()->getFractionDigits();

        $tax1 = $tax2 = $tax3 = 0.0;
        if ($addTaxes) {
            if (
                $service->getTariff()
                && $service->getTariff()->getTaxable()
                && $service->getTariff()->getTax()
            ) {
                $tax1 += $this->calculateTax($price, $service->getTariff()->getTax(), $fractionDigits);
            } else {
                $tax1 += $this->calculateTax($price, $service->getTax1(), $fractionDigits);
                $tax2 += $this->calculateTax($price, $service->getTax2(), $fractionDigits);
                $tax3 += $this->calculateTax($price, $service->getTax3(), $fractionDigits);
            }
        }

        $quantity = $this->getQuantity($service, $invoicedFrom, $invoicedTo);
        foreach ($service->getServiceSurcharges() as $surcharge) {
            $surchargePrice = $surcharge->getInheritedPrice() * $quantity;

            // Round for consistency with invoice where taxes are also calculated from rounded price.
            if ($this->options->get(Option::INVOICE_ITEM_ROUNDING) === FinancialInterface::ITEM_ROUNDING_STANDARD) {
                $surchargePrice = round(
                    $surchargePrice,
                    $service->getClient()->getOrganization()->getCurrency()->getFractionDigits()
                );
            }

            if ($addTaxes && $surcharge->getTaxable()) {
                if ($surcharge->getSurcharge()->getTax()) {
                    $tax1 += $this->calculateTax(
                        $surchargePrice,
                        $surcharge->getSurcharge()->getTax(),
                        $fractionDigits
                    );
                } elseif (
                    $service->getTariff()
                    && $service->getTariff()->getTaxable()
                    && $service->getTariff()->getTax()
                ) {
                    $tax1 += $this->calculateTax($surchargePrice, $service->getTariff()->getTax(), $fractionDigits);
                } else {
                    $tax1 += $this->calculateTax($surchargePrice, $service->getTax1(), $fractionDigits);
                    $tax2 += $this->calculateTax($surchargePrice, $service->getTax2(), $fractionDigits);
                    $tax3 += $this->calculateTax($surchargePrice, $service->getTax3(), $fractionDigits);
                }
            }

            $price += $surchargePrice;
        }

        return round(
            round($price, $fractionDigits)
            + round($tax1, $fractionDigits)
            + round($tax2, $fractionDigits)
            + round($tax3, $fractionDigits),
            $fractionDigits
        );
    }

    public function getSubtotalForPriceSummary(Service $service): float
    {
        $price = $this->getBasePrice($service);
        foreach ($service->getServiceSurcharges() as $surcharge) {
            $price += $surcharge->getInheritedPrice();
        }

        return $price;
    }

    public function getTaxSubtotalForPriceSummary(Service $service, Tax $tax): float
    {
        $fractionDigits = $service->getClient()->getOrganization()->getCurrency()->getFractionDigits();
        $price = $this->getBasePrice($service);
        $taxSubtotal = $this->calculateTax($price, $tax, $fractionDigits);

        foreach ($service->getServiceSurcharges() as $surcharge) {
            if (
                ! $surcharge->getTaxable()
                || (
                    $surcharge->getTaxable()
                    && $surcharge->getSurcharge()->getTax()
                    && $surcharge->getSurcharge()->getTax() !== $tax
                )
            ) {
                continue;
            }

            $taxSubtotal += $this->calculateTax($surcharge->getInheritedPrice(), $tax, $fractionDigits);
        }

        return round($taxSubtotal, $fractionDigits);
    }

    /**
     * Get calculated discount price for next invoiced period.
     */
    public function getDiscountPrice(
        Service $service,
        ?\DateTime $invoicedFrom = null,
        ?\DateTime $invoicedTo = null
    ): float {
        $discountPrice = $service->getDiscountPriceSinglePeriod();
        [$invoicedFrom, $invoicedTo] = $this->getPeriod($service, $invoicedFrom, $invoicedTo);

        $discountQuantity = 0.0;
        if ($discountPrice !== 0.0) {
            $discountQuantity = $this->getDiscountQuantity($service, $invoicedFrom, $invoicedTo);
        }

        return $discountPrice * $discountQuantity;
    }

    public function getDiscountQuantity(
        Service $service,
        ?\DateTime $invoicedFrom = null,
        ?\DateTime $invoicedTo = null
    ): float {
        $discountFrom = $service->getDiscountFrom();
        $discountTo = $service->getDiscountTo();
        $discountFrom = $discountFrom === null || $discountFrom < $invoicedFrom ? $invoicedFrom : $discountFrom;
        $discountTo = $discountTo === null || $discountTo > $invoicedTo ? $invoicedTo : $discountTo;

        $discountQuantity = 0.0;
        if ($discountFrom <= $discountTo) {
            $discountQuantity = Invoicing::getPeriodQuantity(
                $discountFrom,
                $discountTo,
                $service->getTariffPeriodMonths(),
                $service->getInvoicingPeriodStartDay(),
                (int) $this->options->get(Option::BILLING_CYCLE_TYPE)
            );
        }

        return $discountQuantity;
    }

    public function getSurchargeTaxes(Service $service): array
    {
        $currency = $service->getClient()->getOrganization()->getCurrency();
        $fractionDigits = $currency ? $currency->getFractionDigits() : 0;

        $surchargeTaxes = [];
        foreach ($service->getServiceSurcharges() as $surcharge) {
            if ($surcharge->getTaxable() && $tax = $surcharge->getSurcharge()->getTax()) {
                $taxValue = $this->calculateTax($surcharge->getInheritedPrice(), $tax, $fractionDigits);

                if (! array_key_exists($tax->getId(), $surchargeTaxes)) {
                    $surchargeTaxes[$tax->getId()] = [
                        'tax' => $tax,
                        'total' => $taxValue,
                    ];
                } else {
                    $surchargeTaxes[$tax->getId()]['total'] += $taxValue;
                }
            }
        }

        return $surchargeTaxes;
    }

    private function getBasePrice(
        Service $service,
        ?\DateTime $invoicedFrom = null,
        ?\DateTime $invoicedTo = null
    ): float {
        [$invoicedFrom, $invoicedTo] = $this->getPeriod($service, $invoicedFrom, $invoicedTo);
        $quantity = $this->getQuantity($service, $invoicedFrom, $invoicedTo);

        $price = $service->getPrice();
        $price = $price * $quantity;

        // discount can be limited by dates, so quantity is calculated again just for discount
        $price -= $this->getDiscountPrice($service, $invoicedFrom, $invoicedTo);

        $fractionDigits = $service->getClient()->getOrganization()->getCurrency()->getFractionDigits();

        // Round for consistency with invoice where taxes are also calculated from rounded price.
        return $this->options->get(Option::INVOICE_ITEM_ROUNDING) === FinancialInterface::ITEM_ROUNDING_STANDARD
            ? round($price, $fractionDigits)
            : $price;
    }

    private function getPeriod(
        Service $service,
        ?\DateTime $invoicedFrom = null,
        ?\DateTime $invoicedTo = null
    ): array {
        // use single whole period, if not given
        if (! $invoicedFrom || ! $invoicedTo) {
            if ($service->getInvoicingLastPeriodEnd()) {
                $invoicedFrom = (clone $service->getInvoicingLastPeriodEnd())->modify('+1 day');
            } else {
                $invoicedFrom = $service->getInvoicingStart();
            }

            $period = Invoicing::getWholePeriod(
                $invoicedFrom,
                $service->getInvoicingPeriodStartDay(),
                $service->getTariffPeriodMonths()
            );
            $invoicedFrom = $period['invoicedFrom'];
            $invoicedTo = $period['invoicedTo'];
        }

        return [$invoicedFrom, $invoicedTo];
    }

    private function getQuantity(
        Service $service,
        ?\DateTime $invoicedFrom = null,
        ?\DateTime $invoicedTo = null
    ): float {
        [$invoicedFrom, $invoicedTo] = $this->getPeriod($service, $invoicedFrom, $invoicedTo);

        return Invoicing::getPeriodQuantity(
            $invoicedFrom,
            $invoicedTo,
            $service->getTariffPeriodMonths(),
            $service->getInvoicingPeriodStartDay(),
            (int) $this->options->get(Option::BILLING_CYCLE_TYPE)
        );
    }

    private function calculateTax(float $price, ?Tax $tax, int $fractionDigits): float
    {
        if (! $tax || ! $tax->getRate()) {
            return 0.0;
        }

        if ($this->options->get(Option::INVOICE_ITEM_ROUNDING) === FinancialInterface::ITEM_ROUNDING_STANDARD) {
            $price = round($price, $fractionDigits);
        }

        $tax = $this->taxCalculator->calculateTax(
            $price,
            $tax->getRate(),
            $this->options->get(Option::PRICING_MODE),
            $this->options->get(Option::PRICING_TAX_COEFFICIENT_PRECISION)
        );

        if ($this->options->get(Option::INVOICE_TAX_ROUNDING) === FinancialInterface::TAX_ROUNDING_PER_ITEM) {
            $tax = round($tax, $fractionDigits);
        }

        return $tax;
    }
}
