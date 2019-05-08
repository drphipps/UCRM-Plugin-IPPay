<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Service\Facade;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use TicketingBundle\Entity\TicketCannedResponse;

class TicketCannedResponseFacade
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function handleCreate(TicketCannedResponse $ticketCannedResponse): void
    {
        $this->entityManager->persist($ticketCannedResponse);
        $this->entityManager->flush();
    }

    public function handleUpdate(TicketCannedResponse $ticketCannedResponse): void
    {
        $this->entityManager->flush();
    }

    public function handleCollection(Collection $before, Collection $after): void
    {
        foreach ($after as $item) {
            if (! $before->contains($item)) {
                $this->entityManager->persist($item);
            }
        }

        foreach ($before as $item) {
            if (! $after->contains($item)) {
                $this->entityManager->remove($item);
            }
        }

        $this->entityManager->flush();
    }

    public function handleDelete(TicketCannedResponse $ticketCannedResponse): void
    {
        $this->entityManager->remove($ticketCannedResponse);
        $this->entityManager->flush();
    }
}
