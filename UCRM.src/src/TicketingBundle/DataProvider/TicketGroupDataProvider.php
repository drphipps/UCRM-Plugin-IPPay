<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\DataProvider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use TicketingBundle\Entity\TicketGroup;

class TicketGroupDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function find(int $id): ?TicketGroup
    {
        return $this->entityManager
            ->getRepository(TicketGroup::class)
            ->find($id);
    }

    public function findAllForm(): array
    {
        $qb = $this->entityManager
            ->getRepository(TicketGroup::class)
            ->createQueryBuilder('tg', 'tg.id')
            ->select('tg.id, tg.name')
            ->orderBy('tg.name', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function getGridModel(): QueryBuilder
    {
        return $this->entityManager->getRepository(TicketGroup::class)->createQueryBuilder('tg');
    }

    public function findAllTicketGroups()
    {
        return $this->entityManager
            ->getRepository(TicketGroup::class)
            ->findBy(
            [],
            [
                'name' => 'ASC',
            ]
        );
    }
}
