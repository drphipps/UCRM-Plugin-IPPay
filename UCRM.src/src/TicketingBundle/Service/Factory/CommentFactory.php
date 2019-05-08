<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Service\Factory;

use TicketingBundle\Entity\Ticket;
use TicketingBundle\Entity\TicketComment;

class CommentFactory
{
    public function create(Ticket $ticket): TicketComment
    {
        $ticketComment = new TicketComment();
        $ticketComment->setTicket($ticket);

        return $ticketComment;
    }
}
