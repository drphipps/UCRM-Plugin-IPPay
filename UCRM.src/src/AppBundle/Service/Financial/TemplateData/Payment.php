<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Financial\TemplateData;

class Payment
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $method;

    /**
     * @var string|null
     */
    public $checkNumber;

    /**
     * @var string
     */
    public $createdDate;

    /**
     * @var string
     */
    public $createdDateISO8601;

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
    public $note;

    /**
     * @var string
     */
    public $currency;

    /**
     * @var PaymentCover[]
     */
    public $covers;

    /**
     * @var string|null
     */
    public $credit;

    /**
     * @var float|null
     */
    public $creditRaw;

    /**
     * @var string|null
     */
    public $receiptNumber;
}
