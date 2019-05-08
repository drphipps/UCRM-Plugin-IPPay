<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Entity;

use AppBundle\Entity\Client;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class TicketClientAssignment extends TicketActivity implements TicketActivityAssignmentInterface
{
    use TicketActivityAssignmentTrait;

    /**
     * @var Client|null
     *
     * @ORM\ManyToOne(targetEntity="\AppBundle\Entity\Client")
     * @ORM\JoinColumn(referencedColumnName="client_id", nullable=true, onDelete="SET NULL")
     */
    private $assignedClient;

    public function getAssignedClient(): ?Client
    {
        return $this->assignedClient;
    }

    public function setAssignedClient(?Client $assignedClient): void
    {
        $this->assignedClient = $assignedClient;
    }
}
