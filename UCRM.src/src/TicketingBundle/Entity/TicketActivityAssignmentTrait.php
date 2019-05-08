<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait TicketActivityAssignmentTrait
{
    /**
     * @var string
     *
     * @ORM\Column(length=20, options={"default": TicketActivityAssignmentInterface::TYPE_ADD})
     * @Assert\NotNull()
     * @Assert\Length(max = 20)
     * @Assert\Choice(choices=TicketActivityAssignmentInterface::POSSIBLE_TYPES, strict=true)
     */
    private $type = TicketActivityAssignmentInterface::TYPE_ADD;

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }
}
