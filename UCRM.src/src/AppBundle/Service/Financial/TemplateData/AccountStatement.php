<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Financial\TemplateData;

class AccountStatement
{
    /**
     * @var string
     */
    public $currency;

    /**
     * @var AccountStatementItem[]
     */
    public $items;

    /**
     * @var string
     */
    public $initialBalance;

    /**
     * @var float
     */
    public $initialBalanceRaw;

    /**
     * @var string
     */
    public $finalBalance;

    /**
     * @var float
     */
    public $finalBalanceRaw;

    /**
     * @var string
     */
    public $createdDate;

    /**
     * @var string|null
     */
    public $startDate;

    /**
     * @var string|null
     */
    public $endDate;
}
