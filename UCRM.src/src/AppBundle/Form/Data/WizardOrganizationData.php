<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data;

use AppBundle\Component\Annotation\Identifier;
use AppBundle\Entity\Option;
use AppBundle\Entity\Organization;
use AppBundle\Form\Data\Settings\SettingsDataInterface;
use Symfony\Component\Validator\Constraints as Assert;

class WizardOrganizationData implements SettingsDataInterface
{
    /**
     * @var Organization
     */
    public $organization;

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
}
