<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Request;

use AppBundle\Entity\Client;
use AppBundle\Entity\User;
use TicketingBundle\Entity\Ticket;

class JobCollectionRequest
{
    /**
     * @var User|null
     */
    public $user;

    /**
     * @var Client|null
     */
    public $client;

    /**
     * @var Ticket|null
     */
    public $ticket;

    /**
     * @var \DateTimeInterface|null
     */
    public $startDate;

    /**
     * @var \DateTimeInterface|null
     */
    public $endDate;

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
     * @var array|null
     */
    public $filterNullRelations;

    /**
     * @var bool|null
     */
    public $public;
}
