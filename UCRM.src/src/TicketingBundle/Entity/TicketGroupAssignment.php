<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class TicketGroupAssignment extends TicketActivity implements TicketActivityAssignmentInterface
{
    use TicketActivityAssignmentTrait;

    /**
     * @var TicketGroup|null
     *
     * @ORM\ManyToOne(targetEntity="TicketingBundle\Entity\TicketGroup")
     * @ORM\JoinColumn(referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $assignedGroup;

    public function getAssignedGroup(): ?TicketGroup
    {
        return $this->assignedGroup;
    }

    public function setAssignedGroup(?TicketGroup $assignedGroup): void
    {
        $this->assignedGroup = $assignedGroup;
    }
}
