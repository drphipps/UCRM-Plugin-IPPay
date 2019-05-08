<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Financial\TemplateData;

class FinancialItem
{
    /**
     * @var string
     */
    public $label;

    /**
     * @var string
     */
    public $price;

    /**
     * @var float|null
     */
    public $priceRaw;

    /**
     * @var string
     */
    public $quantity;

    /**
     * @var float|null
     */
    public $quantityRaw;

    /**
     * @var string
     */
    public $unit;

    /**
     * @var string|null
     */
    public $priceUntaxed;

    /**
     * @var float|null
     */
    public $priceUntaxedRaw;

    /**
     * @var string|null
     */
    public $totalUntaxed;

    /**
     * @var float|null
     */
    public $totalUntaxedRaw;

    /**
     * @var string|null
     */
    public $taxRate;

    /**
     * @var float|null
     */
    public $taxRateRaw;

    /**
     * @var string|null
     */
    public $taxAmount;

    /**
     * @var float|null
     */
    public $taxAmountRaw;

    /**
     * @var string
     */
    public $total;

    /**
     * @var float
     */
    public $totalRaw;

    /**
     * @var string
     */
    public $type;

    /**
     * @var array|FinancialItem[]
     */
    public $children = [];
}
