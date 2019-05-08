<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Form\Data;

use AppBundle\Entity\Client;
use AppBundle\Entity\User;

class TicketAssignData
{
    /**
     * @var User|null
     */
    public $assignedUser;

    /**
     * @var Client|null
     */
    public $assignedClient;

    /**
     * @var bool
     */
    public $addContact = false;
}
