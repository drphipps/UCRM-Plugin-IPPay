<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 *
 * @see https://www.ubnt.com/
 */
declare(strict_types=1);

namespace TicketingBundle\Form\Data;

class TicketDeleteData
{
    /**
     * @var bool
     */
    public $addToBlacklist = false;

    /**
     * @var bool
     */
    public $deleteTickets = false;
}
