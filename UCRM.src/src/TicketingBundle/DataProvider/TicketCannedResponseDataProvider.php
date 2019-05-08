<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\DataProvider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use TicketingBundle\Entity\TicketCannedResponse;

class TicketCannedResponseDataProvider
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return TicketCannedResponse[]
     */
    public function getAll(): array
    {
        $qb = $this->entityManager
            ->getRepository(TicketCannedResponse::class)
            ->createQueryBuilder('tcr')
            ->select('tcr')
            ->orderBy('tcr.name', 'ASC')
            ->addOrderBy('tcr.id', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function getAllPairs(): array
    {
        $qb = $this->entityManager
            ->getRepository(TicketCannedResponse::class)
            ->createQueryBuilder('tcr', 'tcr.id')
            ->select('tcr.name, tcr.content')
            ->orderBy('tcr.name', 'ASC')
            ->addOrderBy('tcr.id', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function getGridModel(): QueryBuilder
    {
        return $this->entityManager->getRepository(TicketCannedResponse::class)->createQueryBuilder('tcr');
    }
}
