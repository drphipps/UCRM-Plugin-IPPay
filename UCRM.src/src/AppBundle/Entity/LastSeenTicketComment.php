<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use TicketingBundle\Entity\Ticket;
use TicketingBundle\Entity\TicketComment;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\LastSeenTicketCommentRepository")
 */
class LastSeenTicketComment
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(referencedColumnName="user_id", nullable=false, onDelete="CASCADE")
     */
    protected $user;

    /**
     * @var Ticket
     *
     * @ORM\ManyToOne(targetEntity="TicketingBundle\Entity\Ticket", inversedBy="lastSeenTicketComments")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $ticket;

    /**
     * @var TicketComment
     *
     * @ORM\ManyToOne(targetEntity="TicketingBundle\Entity\TicketComment")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $lastSeenTicketComment;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getTicket(): Ticket
    {
        return $this->ticket;
    }

    public function setTicket(Ticket $ticket): void
    {
        $this->ticket = $ticket;
    }

    public function getLastSeenTicketComment(): TicketComment
    {
        return $this->lastSeenTicketComment;
    }

    public function setLastSeenTicketComment(TicketComment $lastSeenTicketComment): void
    {
        $this->lastSeenTicketComment = $lastSeenTicketComment;
    }
}
