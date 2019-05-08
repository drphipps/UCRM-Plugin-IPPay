<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data\Settings;

use AppBundle\Component\Annotation\Identifier;
use AppBundle\Entity\Option;
use Symfony\Component\Validator\Constraints as Assert;

final class BillingData implements SettingsDataInterface
{
    /**
     * @var int
     *
     * @Identifier(Option::INVOICE_TIME_HOUR)
     *
     * @Assert\Range(min=0, max=23)
     * @Assert\NotBlank()
     */
    public $invoiceTimeHour;

    /**
     * @var int
     *
     * @Identifier(Option::BILLING_CYCLE_TYPE)
     */
    public $billingCycleType;

    /**
     * @var int
     *
     * @Identifier(Option::INVOICING_PERIOD_TYPE)
     */
    public $invoicingPeriodType;

    /**
     * @var int
     *
     * @Identifier(Option::INVOICE_PERIOD_START_DAY)
     */
    public $invoicePeriodStartDay;

    /**
     * @var string
     *
     * @Identifier(Option::DISCOUNT_INVOICE_LABEL)
     *
     * @Assert\Length(max = 100)
     */
    public $discountInvoiceLabel;

    /**
     * @var int
     *
     * @Identifier(Option::INVOICE_ITEM_ROUNDING)
     */
    public $invoiceItemRounding;

    /**
     * @var int
     *
     * @Identifier(Option::INVOICE_TAX_ROUNDING)
     */
    public $invoiceTaxRounding;

    /**
     * @var int
     *
     * @Identifier(Option::PRICING_MODE)
     *
     * @Assert\Expression(
     *     expression="value !== constant('\\AppBundle\\Entity\\Option::PRICING_MODE_WITH_TAXES') or this.invoiceItemRounding === constant('\\AppBundle\\Entity\\Financial\\FinancialInterface::ITEM_ROUNDING_STANDARD')",
     *     message="Tax inclusive pricing cannot be combined with non-rounded item totals."
     * )
     */
    public $pricingMode;

    /**
     * @var bool
     *
     * @Identifier(Option::PRICING_MULTIPLE_TAXES)
     */
    public $pricingMultipleTaxes;

    /**
     * @var int
     *
     * @Identifier(Option::PRICING_TAX_COEFFICIENT_PRECISION)
     *
     * @Assert\GreaterThan(0)
     */
    public $pricingTaxCoefficientPrecision;

    /**
     * @var bool
     *
     * @Identifier(Option::SEND_INVOICE_BY_EMAIL)
     */
    public $sendInvoiceByEmail;

    /**
     * @var bool
     *
     * @Identifier(Option::SEND_INVOICE_BY_POST)
     */
    public $sendInvoiceByPost;

    /**
     * @var bool
     *
     * @Identifier(Option::STOP_INVOICING)
     */
    public $stopInvoicing;

    /**
     * @var bool
     *
     * @Identifier(Option::SUBSCRIPTIONS_ENABLED_CUSTOM)
     */
    public $subscriptionsEnabledCustom;

    /**
     * @var bool
     *
     * @Identifier(Option::SUBSCRIPTIONS_ENABLED_LINKED)
     */
    public $subscriptionsEnabledLinked;

    /**
     * @var int
     *
     * @Identifier(Option::SERVICE_INVOICING_DAY_ADJUSTMENT)
     *
     * @Assert\NotBlank()
     * @Assert\Range(min="0", max="730")
     */
    public $serviceInvoicingDayAdjustment;

    /**
     * @var bool
     *
     * @Identifier(Option::GENERATE_PROFORMA_INVOICES)
     */
    public $generateProformaInvoices;
}
