<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Service\Facade;

use Doctrine\ORM\EntityManagerInterface;
use TicketingBundle\Entity\TicketGroup;

class TicketGroupFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function handleCreate(TicketGroup $ticketGroup): void
    {
        $this->entityManager->persist($ticketGroup);
        $this->entityManager->flush();
    }

    public function handleUpdate(TicketGroup $ticketGroup): void
    {
        $this->entityManager->flush();
    }

    public function handleDelete(TicketGroup $ticketGroup): void
    {
        $this->entityManager->remove($ticketGroup);
        $this->entityManager->flush();
    }
}
