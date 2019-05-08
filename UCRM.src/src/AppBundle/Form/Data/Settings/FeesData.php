<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data\Settings;

use AppBundle\Component\Annotation\Identifier;
use AppBundle\Entity\Option;
use AppBundle\Entity\Tax;
use Symfony\Component\Validator\Constraints as Assert;

final class FeesData implements SettingsDataInterface
{
    /**
     * @var bool
     *
     * @Identifier(Option::LATE_FEE_ACTIVE)
     */
    public $lateFeeActive;

    /**
     * @var int|null
     *
     * @Identifier(Option::LATE_FEE_DELAY_DAYS)
     *
     * @Assert\GreaterThanOrEqual(0)
     */
    public $lateFeeDelayDays;

    /**
     * @var string
     *
     * @Identifier(Option::LATE_FEE_INVOICE_LABEL)
     *
     * @Assert\Length(max=500)
     */
    public $lateFeeInvoiceLabel;

    /**
     * @var float
     *
     * @Identifier(Option::LATE_FEE_PRICE)
     */
    public $lateFeePrice;

    /**
     * @var int
     *
     * @Identifier(Option::LATE_FEE_PRICE_TYPE)
     */
    public $lateFeePriceType;

    /**
     * @var bool
     *
     * @Identifier(Option::LATE_FEE_TAXABLE)
     */
    public $lateFeeTaxable;

    /**
     * @var int|Tax
     *
     * @Identifier(Option::LATE_FEE_TAX_ID)
     */
    public $lateFeeTaxId;

    /**
     * @var string
     *
     * @Identifier(Option::SETUP_FEE_INVOICE_LABEL)
     */
    public $setupFeeInvoiceLabel;

    /**
     * @var bool
     *
     * @Identifier(Option::SETUP_FEE_TAXABLE)
     */
    public $setupFeeTaxable;

    /**
     * @var int|Tax
     *
     * @Identifier(Option::SETUP_FEE_TAX_ID)
     */
    public $setupFeeTaxId;

    /**
     * @var string
     *
     * @Identifier(Option::EARLY_TERMINATION_FEE_INVOICE_LABEL)
     */
    public $earlyTerminationFeeInvoiceLabel;

    /**
     * @var bool
     *
     * @Identifier(Option::EARLY_TERMINATION_FEE_TAXABLE)
     */
    public $earlyTerminationFeeTaxable;

    /**
     * @var int|Tax
     *
     * @Identifier(Option::EARLY_TERMINATION_FEE_TAX_ID)
     */
    public $earlyTerminationFeeTaxId;
}
