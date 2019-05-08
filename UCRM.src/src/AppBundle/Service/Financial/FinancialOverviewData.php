<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Financial;

use ArrayIterator;

class FinancialOverviewData implements \IteratorAggregate
{
    /**
     * @var float|null
     */
    public $totalDue;

    /**
     * @var float|null
     */
    public $totalOverdue;

    /**
     * @var float|null
     */
    public $invoicedThisMonth;

    /**
     * @var float|null
     */
    public $invoicedThisMonthUnpaid;

    /**
     * @var string|null
     */
    public $locale;

    public function getIterator()
    {
        return new ArrayIterator($this);
    }
}
