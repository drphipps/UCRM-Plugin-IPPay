<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Entity;

use AppBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 */
class TicketGroup
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotNull()
     * @Assert\Length(max = 255)
     */
    private $name;

    /**
     * @var Collection|Ticket[]
     *
     * @ORM\OneToMany(targetEntity="TicketingBundle\Entity\Ticket", mappedBy="group")
     */
    private $tickets;

    /**
     * @var Collection|User[]
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\User", mappedBy="ticketGroups", cascade={"persist"})
     */
    private $users;

    /**
     * @var Collection|TicketImapInbox[]
     *
     * @ORM\OneToMany(targetEntity="TicketingBundle\Entity\TicketImapInbox", mappedBy="ticketGroup")
     */
    private $ticketImapInboxes;

    public function __construct()
    {
        $this->tickets = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->ticketImapInboxes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function addTicket(Ticket $ticket): void
    {
        $this->tickets->add($ticket);
    }

    public function removeTicket(Ticket $ticket): void
    {
        $this->tickets->removeElement($ticket);
    }

    /**
     * @return Collection|Ticket[]
     */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    /**
     * @return Collection|User[]
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): void
    {
        if ($this->users->contains($user)) {
            return;
        }

        $this->users->add($user);
        $user->addTicketGroup($this);
    }

    public function removeUser(User $user): void
    {
        if (! $this->users->contains($user)) {
            return;
        }

        $this->users->removeElement($user);
        $user->removeTicketGroup($this);
    }

    public function addTicketImapInbox(TicketImapInbox $ticketImapInbox): void
    {
        $this->ticketImapInboxes->add($ticketImapInbox);
    }

    public function removeTicketImapInbox(TicketImapInbox $ticketImapInbox): void
    {
        $this->ticketImapInboxes->removeElement($ticketImapInbox);
    }

    /**
     * @return Collection|TicketImapInbox[]
     */
    public function getTicketImapInboxes(): Collection
    {
        return $this->ticketImapInboxes;
    }
}
