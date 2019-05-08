<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use SchedulingBundle\Entity\Job;

/**
 * @ORM\Entity()
 */
class TicketJobAssignment extends TicketActivity implements TicketActivityAssignmentInterface
{
    use TicketActivityAssignmentTrait;

    /**
     * @var Job|null
     *
     * @ORM\ManyToOne(targetEntity="SchedulingBundle\Entity\Job")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $assignedJob;

    public function getAssignedJob(): ?Job
    {
        return $this->assignedJob;
    }

    public function setAssignedJob(?Job $assignedJob): void
    {
        $this->assignedJob = $assignedJob;
    }
}
