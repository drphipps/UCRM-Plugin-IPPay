<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Repository;

use AppBundle\Entity\User;
use AppBundle\Repository\BaseRepository;
use Doctrine\ORM\QueryBuilder;
use TicketingBundle\Entity\Ticket;
use TicketingBundle\Interfaces\TicketingActionsInterface;
use TicketingBundle\Request\TicketsRequest;

class TicketRepository extends BaseRepository
{
    public function createElasticQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('t');
    }

    public function getCount(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getQueryBuilder(TicketsRequest $ticketsRequest): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t')
            ->addSelect('cl')
            ->addSelect('cl_u')
            ->leftJoin('t.client', 'cl')
            ->leftJoin('cl.user', 'cl_u')
            ->setMaxResults($ticketsRequest->limit);

        $statusFilters = $this->getStatusFiltersParameter($ticketsRequest->statusFilters);
        if ($statusFilters) {
            $qb
                ->andWhere('t.status IN (:statuses)')
                ->setParameter('statuses', $statusFilters);
        }

        if ($ticketsRequest->lastTimestamp) {
            $qb
                ->andWhere('t.lastActivity < :lastTimestamp')
                ->setParameter('lastTimestamp', $ticketsRequest->lastTimestamp);
        }

        if ($ticketsRequest->userFilter instanceof User) {
            $qb
                ->andWhere('t.assignedUser = :assignedUser')
                ->setParameter('assignedUser', $ticketsRequest->userFilter);
        } elseif ($ticketsRequest->userFilter === TicketingActionsInterface::USER_FILTER_UNASSIGNED) {
            $qb
                ->andWhere('t.assignedUser IS NULL');
        }

        if ($ticketsRequest->groupFilter) {
            $qb
                ->andWhere('t.group = :group')
                ->setParameter('group', $ticketsRequest->groupFilter);
        }

        if ($ticketsRequest->client) {
            $qb
                ->andWhere('t.client = :client')
                ->setParameter('client', $ticketsRequest->client);
        }

        if ($ticketsRequest->lastActivityFilter && $ticketsRequest->lastActivityFilter !== TicketingActionsInterface::LAST_ACTIVITY_FILTER_ALL) {
            $qb
                ->andWhere('t.isLastActivityByClient = :isLastActivityByClient')
                ->setParameter(
                    'isLastActivityByClient',
                    $ticketsRequest->lastActivityFilter === TicketingActionsInterface::LAST_ACTIVITY_FILTER_CLIENT
                );
        }

        if ($ticketsRequest->public !== null) {
            $qb
                ->andWhere('t.public = :public')
                ->setParameter('public', $ticketsRequest->public);
        }

        return $qb;
    }

    public function getStatusFiltersParameter(array $statusFilters): array
    {
        $statusFilters = array_keys(array_filter($statusFilters));

        return array_map(
            function ($status) {
                return Ticket::STATUS_MAP[$status];
            },
            $statusFilters
        );
    }
}
