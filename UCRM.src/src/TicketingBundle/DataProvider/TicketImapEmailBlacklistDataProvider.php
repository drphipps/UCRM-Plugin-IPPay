<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */
declare(strict_types=1);

namespace TicketingBundle\DataProvider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use TicketingBundle\Entity\TicketImapEmailBlacklist;

class TicketImapEmailBlacklistDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getGridModel(): QueryBuilder
    {
        return $this->entityManager->getRepository(TicketImapEmailBlacklist::class)
            ->createQueryBuilder('tieb');
    }

    public function exists(string $emailAddress): bool
    {
        return (bool) $this->entityManager->getRepository(TicketImapEmailBlacklist::class)
            ->createQueryBuilder('b')
            ->select('1')
            ->where('b.emailAddress = :emailAddress')
            ->setParameter('emailAddress', $emailAddress)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
