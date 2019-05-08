<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Form\Data;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use TicketingBundle\Entity\TicketCannedResponse;

class TicketCannedResponseCollection
{
    /**
     * @var Collection|TicketCannedResponse[]
     *
     * @Assert\Valid()
     */
    private $ticketCannedResponses;

    public function __construct()
    {
        $this->ticketCannedResponses = new ArrayCollection();
    }

    public function addTicketCannedResponse(TicketCannedResponse $ticketCannedResponse): void
    {
        $this->ticketCannedResponses[] = $ticketCannedResponse;
    }

    public function removeTicketCannedResponse(TicketCannedResponse $ticketCannedResponse): void
    {
        $this->ticketCannedResponses->removeElement($ticketCannedResponse);
    }

    /**
     * @return Collection|TicketCannedResponse[]
     */
    public function getTicketCannedResponses(): Collection
    {
        return $this->ticketCannedResponses;
    }

    public function setTicketCannedResponses(Collection $ticketCannedResponses): void
    {
        $this->ticketCannedResponses = $ticketCannedResponses;
    }
}
