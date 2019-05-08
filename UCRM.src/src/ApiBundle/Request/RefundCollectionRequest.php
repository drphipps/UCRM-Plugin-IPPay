<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Request;

use AppBundle\Entity\Currency;

class RefundCollectionRequest
{
    /**
     * @var int|null
     */
    public $clientId;

    /**
     * @var \DateTimeInterface|null
     */
    public $startDate;

    /**
     * @var \DateTimeInterface|null
     */
    public $endDate;

    /**
     * @var int|null
     */
    public $limit;

    /**
     * @var int|null
     */
    public $offset;

    /**
     * @var string|null
     */
    public $order;

    /**
     * @var string|null
     */
    public $direction;

    /**
     * @var Currency|null
     */
    public $currency;
}
