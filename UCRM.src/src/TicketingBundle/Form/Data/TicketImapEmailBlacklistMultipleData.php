<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Form\Data;

use Symfony\Component\Validator\Constraints as Assert;

class TicketImapEmailBlacklistMultipleData
{
    /**
     * @var string
     *
     * @Assert\NotBlank()
     */
    public $emailAddresses;

    /**
     * @var bool
     */
    public $deleteTickets = false;
}
