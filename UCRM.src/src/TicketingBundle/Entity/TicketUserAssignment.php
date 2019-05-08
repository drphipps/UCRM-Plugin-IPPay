<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Entity;

use AppBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class TicketUserAssignment extends TicketActivity implements TicketActivityAssignmentInterface
{
    use TicketActivityAssignmentTrait;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="\AppBundle\Entity\User")
     * @ORM\JoinColumn(referencedColumnName="user_id", nullable=true, onDelete="SET NULL")
     */
    private $assignedUser;

    public function getAssignedUser(): ?User
    {
        return $this->assignedUser;
    }

    public function setAssignedUser(?User $assignedUser): void
    {
        $this->assignedUser = $assignedUser;
    }
}
