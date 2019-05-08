<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\Client;
use AppBundle\Entity\Credit;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Payment;
use AppBundle\Entity\Refund;
use AppBundle\Entity\Service;
use Doctrine\ORM\EntityManagerInterface;
use SchedulingBundle\Entity\Job;
use TicketingBundle\Entity\Ticket;

class SandboxDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    public function getOverview(): array
    {
        return [
            'clientCount' => $this->entityManager->getRepository(Client::class)->getCount(),
            'serviceCount' => $this->entityManager->getRepository(Service::class)->getCount(),
            'invoiceCount' => $this->entityManager->getRepository(Invoice::class)->getCount(),
            'paymentCount' => $this->entityManager->getRepository(Payment::class)->getCount()
                + $this->entityManager->getRepository(Refund::class)->getCount()
                + $this->entityManager->getRepository(Credit::class)->getCount(),
            'ticketCount' => $this->entityManager->getRepository(Ticket::class)->getCount(),
            'jobCount' => $this->entityManager->getRepository(Job::class)->getCount(),
        ];
    }
}
