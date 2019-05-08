<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Request;

use AppBundle\Entity\Client;

class QuoteCollectionRequest
{
    /**
     * @var Client|null
     */
    public $client;

    /**
     * @var array|null
     */
    public $statuses;

    /**
     * @var int|null
     */
    public $limit;

    /**
     * @var int|null
     */
    public $offset;

    /**
     * @var \DateTime|null
     */
    public $startDate;

    /**
     * @var \DateTime|null
     */
    public $endDate;

    /**
     * @var string|null
     */
    public $number;

    /**
     * @var string|null
     */
    public $order;

    /**
     * @var string|null
     */
    public $direction;
}
