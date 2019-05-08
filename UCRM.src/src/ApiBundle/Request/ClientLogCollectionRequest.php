<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Request;

use AppBundle\Entity\Client;

class ClientLogCollectionRequest
{
    /**
     * @var Client|null
     */
    public $client;

    /**
     * @var \DateTime|null
     */
    public $startDate;

    /**
     * @var \DateTime|null
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
}
