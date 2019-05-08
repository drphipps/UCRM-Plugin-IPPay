<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\User;

use AppBundle\Entity\User;
use Symfony\Component\EventDispatcher\Event;
use TicketingBundle\Entity\TicketComment;

final class TicketCommentSeenEvent extends Event
{
    /**
     * @var User
     */
    private $user;

    /**
     * @var TicketComment
     */
    private $ticketComment;

    public function __construct(User $user, TicketComment $ticketComment)
    {
        $this->user = $user;
        $this->ticketComment = $ticketComment;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTicketComment(): TicketComment
    {
        return $this->ticketComment;
    }
}
