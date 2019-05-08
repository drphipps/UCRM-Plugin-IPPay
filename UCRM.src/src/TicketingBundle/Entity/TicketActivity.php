<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Entity;

use AppBundle\Entity\AppKey;
use AppBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\InheritanceType("JOINED")
 *
 * Keep properties in this class "protected", otherwise API validation does not work correctly.
 */
abstract class TicketActivity
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
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":true})
     */
    protected $public = true;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime_utc")
     */
    protected $createdAt;

    /**
     * @var Ticket
     *
     * @ORM\ManyToOne(targetEntity="Ticket", inversedBy="activity")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @Assert\NotNull()
     */
    protected $ticket;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="\AppBundle\Entity\User")
     * @ORM\JoinColumn(referencedColumnName="user_id", onDelete="SET NULL")
     */
    protected $user;

    /**
     * @var AppKey|null
     *
     * @ORM\ManyToOne(targetEntity="\AppBundle\Entity\AppKey")
     * @ORM\JoinColumn(referencedColumnName="key_id", onDelete="SET NULL")
     */
    protected $appKey;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function setPublic(bool $public): void
    {
        $this->public = $public;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getTicket(): Ticket
    {
        return $this->ticket;
    }

    public function setTicket(Ticket $ticket): void
    {
        $this->ticket = $ticket;
        $ticket->addActivity($this);
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getAppKey(): ?AppKey
    {
        return $this->appKey;
    }

    public function setAppKey(?AppKey $appKey): void
    {
        $this->appKey = $appKey;
    }
}
