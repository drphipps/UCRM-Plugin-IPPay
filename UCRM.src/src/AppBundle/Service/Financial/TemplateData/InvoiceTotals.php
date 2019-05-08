<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Financial\TemplateData;

class InvoiceTotals extends Totals
{
    /**
     * @var string
     */
    public $amountPaid;

    /**
     * @var float|null
     */
    public $amountPaidRaw;

    /**
     * @var bool
     */
    public $hasPayment;

    /**
     * @var bool
     */
    public $hasCreditPayment;

    /**
     * @var string
     */
    public $amountDue;

    /**
     * @var float|null
     */
    public $amountDueRaw;

    /**
     * @var bool
     */
    public $hasCustomRounding;

    /**
     * @var string
     */
    public $totalRoundingDifference;

    /**
     * @var float
     */
    public $totalRoundingDifferenceRaw;

    /**
     * @var string
     */
    public $totalBeforeRounding;

    /**
     * @var float
     */
    public $totalBeforeRoundingRaw;

    /**
     * @deprecated but not removed for backward compatibility with older invoice templates
     *
     * @var string
     */
    public $balanceDue;
}
