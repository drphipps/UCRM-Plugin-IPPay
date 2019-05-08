<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Request;

use AppBundle\Entity\Client;
use AppBundle\Entity\User;
use TicketingBundle\Entity\TicketGroup;

class TicketsRequest
{
    /**
     * @var Client|null
     */
    public $client;

    /**
     * @var \DateTimeInterface|null
     */
    public $lastTimestamp;

    /**
     * @var int|null
     */
    public $limit;

    /**
     * @var array
     */
    public $statusFilters = [];

    /**
     * @var User|string|null
     */
    public $userFilter;

    /**
     * @var TicketGroup|null
     */
    public $groupFilter;

    /**
     * @var string|null
     */
    public $lastActivityFilter;

    /**
     * @var bool|null
     */
    public $public;
}
