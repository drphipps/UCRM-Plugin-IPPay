<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Request;

use AppBundle\Entity\User;
use TicketingBundle\Entity\Ticket;

class TicketCommentsRequest
{
    /**
     * @var Ticket|null
     */
    public $ticket;

    /**
     * @var User|null
     */
    public $user;

    /**
     * @var \DateTimeInterface|null
     */
    public $startDate;

    /**
     * @var \DateTimeInterface|null
     */
    public $endDate;
}
