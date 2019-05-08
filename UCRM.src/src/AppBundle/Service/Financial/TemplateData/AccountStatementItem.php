<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Financial\TemplateData;

class AccountStatementItem
{
    /**
     * @var string
     */
    public $amount;

    /**
     * @var float
     */
    public $amountRaw;

    /**
     * @var string
     */
    public $currency;

    /**
     * @var string
     */
    public $createdDate;

    /**
     * @var string
     */
    public $createdDateISO8601;

    /**
     * @var Invoice|null
     */
    public $invoice;

    /**
     * @var Payment|null
     */
    public $payment;

    /**
     * @var Refund|null
     */
    public $refund;

    /**
     * @var bool
     */
    public $income;

    /**
     * @var string
     */
    public $balance;

    /**
     * @var float
     */
    public $balanceRaw;
}
