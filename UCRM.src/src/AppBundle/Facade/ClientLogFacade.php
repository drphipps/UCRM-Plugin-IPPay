<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\ClientLog;
use Doctrine\ORM\EntityManagerInterface;

class ClientLogFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function handleNew(ClientLog $clientLog): void
    {
        $this->entityManager->persist($clientLog);
        $this->entityManager->flush();
    }

    public function handleEdit(ClientLog $clientLog): void
    {
        $this->entityManager->flush();
    }

    public function handleDelete(ClientLog $clientLog): void
    {
        $this->entityManager->remove($clientLog);
        $this->entityManager->flush();
    }
}
