<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\DataProvider;

use AppBundle\Database\UtcDateTimeType;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use TicketingBundle\Entity\Ticket;
use TicketingBundle\Entity\TicketActivity;
use TicketingBundle\Entity\TicketComment;
use TicketingBundle\Entity\TicketJobAssignment;
use TicketingBundle\Request\TicketCommentsRequest;

class TicketActivityDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getByTicket(Ticket $ticket, string $orderDirection = 'DESC'): array
    {
        return $this->entityManager
            ->getRepository(TicketActivity::class)
            ->findBy(
                [
                    'ticket' => $ticket,
                ],
                [
                    'createdAt' => $orderDirection,
                ]
            );
    }

    /**
     * We only want to display job widgets in the latest "ADD" assignment activity, if the job is still assigned.
     * This method goes through the activity and returns an array with job IDs as keys and the latest possible
     * activity ID as value.
     *
     * @param TicketActivity[] $activity
     */
    public function getJobWidgetVisibleIds(array $activity): array
    {
        $visibleIds = [];

        foreach ($activity as $item) {
            if (
                ! $item instanceof TicketJobAssignment
                || $item->getType() !== TicketJobAssignment::TYPE_ADD
                || ! $item->getAssignedJob()
                || ! $item->getTicket()->getJobs()->contains($item->getAssignedJob())
            ) {
                continue;
            }

            $visibleIds[$item->getAssignedJob()->getId()] = $item->getId();
        }

        return $visibleIds;
    }

    public function getPublicCommentsByTicket(Ticket $ticket, string $orderDirection = 'DESC'): array
    {
        return $this->entityManager
            ->getRepository(TicketComment::class)
            ->findBy(
                [
                    'public' => true,
                    'ticket' => $ticket,
                ],
                [
                    'createdAt' => $orderDirection,
                ]
            );
    }

    public function getAll(
        ?Ticket $ticket,
        ?User $user,
        ?\DateTimeInterface $startDate,
        ?\DateTimeInterface $endDate
    ): array {
        $qb = $this->entityManager
            ->getRepository(TicketActivity::class)
            ->createQueryBuilder('ta')
            ->addOrderBy('ta.createdAt', 'ASC')
            ->addOrderBy('ta.id', 'ASC');

        if ($ticket) {
            $qb
                ->andWhere('ta.ticket = :ticket')
                ->setParameter('ticket', $ticket);
        }

        if ($user) {
            $qb
                ->andWhere('ta.user = :user')
                ->setParameter('user', $user);
        }

        if ($startDate) {
            $qb
                ->andWhere('ta.createdAt >= :startDate')
                ->setParameter('startDate', $startDate, UtcDateTimeType::NAME);
        }

        if ($endDate) {
            $qb
                ->andWhere('ta.createdAt <= :endDate')
                ->setParameter('endDate', $endDate, UtcDateTimeType::NAME);
        }

        return $qb->getQuery()->getResult();
    }

    public function getAllTicketComments(TicketCommentsRequest $ticketCommentsRequest): array
    {
        $qb = $this->entityManager
            ->getRepository(TicketComment::class)
            ->createQueryBuilder('tc')
            ->addOrderBy('tc.createdAt', 'ASC')
            ->addOrderBy('tc.id', 'ASC');

        if ($ticketCommentsRequest->ticket) {
            $qb
                ->andWhere('tc.ticket = :ticket')
                ->setParameter('ticket', $ticketCommentsRequest->ticket);
        }

        if ($ticketCommentsRequest->user) {
            $qb
                ->andWhere('tc.user = :user')
                ->setParameter('user', $ticketCommentsRequest->user);
        }

        if ($ticketCommentsRequest->startDate) {
            $qb
                ->andWhere('tc.createdDate >= :startDate')
                ->setParameter('startDate', $ticketCommentsRequest->startDate, UtcDateTimeType::NAME);
        }

        if ($ticketCommentsRequest->endDate) {
            $qb
                ->andWhere('tc.createdDate <= :endDate')
                ->setParameter('endDate', $ticketCommentsRequest->endDate, UtcDateTimeType::NAME);
        }

        return $qb->getQuery()->getResult();
    }

    public function findLastTicketComment(Ticket $ticket, bool $onlyPublic): ?TicketComment
    {
        $qb = $this->entityManager
            ->getRepository(TicketComment::class)
            ->createQueryBuilder('tc')
            ->addOrderBy('tc.createdAt', 'DESC')
            ->addOrderBy('tc.id', 'DESC')
            ->andWhere('tc.ticket = :ticket')
            ->setParameter('ticket', $ticket)
            ->setMaxResults(1);

        if ($onlyPublic) {
            $qb->andWhere('tc.public = true');
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
