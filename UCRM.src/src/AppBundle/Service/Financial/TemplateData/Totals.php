<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Financial\TemplateData;

abstract class Totals
{
    /**
     * @var string
     */
    public $subtotal;

    /**
     * @var float|null
     */
    public $subtotalRaw;

    /**
     * @var string
     */
    public $total;

    /**
     * @var float|null
     */
    public $totalRaw;

    /**
     * @var string
     */
    public $discountLabel;

    /**
     * @var string
     */
    public $discountPrice;

    /**
     * @var float|null
     */
    public $discountPriceRaw;

    /**
     * @var bool
     */
    public $hasDiscount;

    /**
     * @var array|TaxTotal[]
     */
    public $taxes = [];

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
     * @var bool
     */
    public $hasTotalRounding;
}
