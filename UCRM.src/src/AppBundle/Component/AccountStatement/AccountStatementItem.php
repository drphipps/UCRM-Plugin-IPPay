<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\AccountStatement;

use AppBundle\Entity\Currency;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Payment;
use AppBundle\Entity\Refund;

class AccountStatementItem
{
    /**
     * @var float
     */
    public $amount;

    /**
     * @var Currency
     */
    public $currency;

    /**
     * @var \DateTimeInterface|null
     */
    public $createdDate;

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
    public $income = false;

    /**
     * @var float
     */
    public $balance;
}
