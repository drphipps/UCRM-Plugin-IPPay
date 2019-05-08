<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Financial;

class FinancialTotalData
{
    /**
     * @var float
     */
    public $subtotal = 0.0;

    /**
     * @var float
     */
    public $totalDiscount = 0.0;

    /**
     * @var float
     */
    public $totalUntaxed = 0.0;

    /**
     * @var float
     */
    public $total = 0.0;

    /**
     * @var float
     */
    public $totalTaxAmount = 0.0;

    /**
     * @var array
     */
    public $totalTaxes = [];

    /**
     * @var array
     */
    public $taxReport = [];
}
