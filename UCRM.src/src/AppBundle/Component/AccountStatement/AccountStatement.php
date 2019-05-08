<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\AccountStatement;

use AppBundle\Entity\Client;
use AppBundle\Entity\Currency;

class AccountStatement
{
    /**
     * @var Client|null
     */
    public $client;

    /**
     * @var Currency|null
     */
    public $currency;

    /**
     * @var AccountStatementItem[]
     */
    public $items = [];

    /**
     * @var float
     */
    public $initialBalance;

    /**
     * @var float
     */
    public $finalBalance;

    /**
     * @var \DateTimeInterface
     */
    public $createdDate;

    /**
     * @var \DateTimeInterface|null
     */
    public $startDate;

    /**
     * @var \DateTimeInterface|null
     */
    public $endDate;

    public function __construct()
    {
        $this->createdDate = new \DateTimeImmutable();
    }
}
