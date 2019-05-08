<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\DataProvider;

use AppBundle\Component\Export\ExportPathData;
use AppBundle\Database\UtcDateTimeType;
use AppBundle\Entity\Client;
use AppBundle\Entity\User;
use AppBundle\Util\Arrays;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Filesystem\Filesystem;
use TicketingBundle\Api\Request\TicketCollectionRequest;
use TicketingBundle\Entity\Ticket;
use TicketingBundle\Entity\TicketComment;
use TicketingBundle\Entity\TicketGroup;
use TicketingBundle\FileManager\CommentAttachmentFileManager;
use TicketingBundle\Interfaces\TicketingActionsInterface;
use TicketingBundle\Request\TicketsRequest;

class TicketDataProvider
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var CommentAttachmentFileManager
     */
    private $commentAttachmentFileManager;

    public function __construct(EntityManager $em, CommentAttachmentFileManager $commentAttachmentFileManager)
    {
        $this->em = $em;
        $this->commentAttachmentFileManager = $commentAttachmentFileManager;
    }

    public function getActiveByAssignedUser(User $user, ?int $limit = null): array
    {
        $qb = $this->em
            ->getRepository(Ticket::class)
            ->createQueryBuilder('t')
            ->addSelect('c')
            ->addSelect('cu')
            ->leftJoin('t.client', 'c')
            ->leftJoin('c.user', 'cu')
            ->leftJoin('t.assignedUser', 'u')
            ->andWhere('t.status IN (:status)')
            ->andWhere('t.assignedUser = :user')
            ->setParameter('status', array_keys(Ticket::STATUSES_IS_ACTIVE))
            ->setParameter('user', $user)
            ->groupBy('t.id, c.id, cu.id')
            ->orderBy('t.lastActivity', 'DESC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function getActiveAll(?int $limit = null): array
    {
        $qb = $this->em
            ->getRepository(Ticket::class)
            ->createQueryBuilder('t')
            ->addSelect('c')
            ->addSelect('cu')
            ->leftJoin('t.client', 'c')
            ->leftJoin('c.user', 'cu')
            ->andWhere('t.status IN (:status)')
            ->setParameter('status', array_keys(Ticket::STATUSES_IS_ACTIVE))
            ->groupBy('t.id, c.id, cu.id')
            ->orderBy('t.lastActivity', 'DESC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Ticket[]
     */
    public function getByEmailFromAddressToDelete(array $emails): array
    {
        $qb = $this->em
            ->getRepository(Ticket::class)
            ->createQueryBuilder('t')
            ->where('t.emailFromAddress IN (:emails)')
            ->andWhere('t.client IS NULL')
            ->setParameter('emails', $emails);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param string|User|null $userFilter
     *
     * @return Ticket[]
     */
    public function getByIds(
        array $ids,
        array $statusFilters = [],
        $userFilter = null,
        ?string $lastActivityFilter = null,
        ?TicketGroup $groupFilter = null
    ): array {
        if (! $ids) {
            return [];
        }

        $ticketRepository = $this->em->getRepository(Ticket::class);
        $qb = $ticketRepository->createQueryBuilder('t')
            ->andWhere('t.id IN (:ids)')
            ->setParameter('ids', $ids);

        $statusFilters = $ticketRepository->getStatusFiltersParameter($statusFilters);
        if ($statusFilters) {
            $qb->andWhere('t.status IN (:statuses)')
                ->setParameter('statuses', $statusFilters);
        }

        if ($userFilter instanceof User) {
            $qb->andWhere('t.assignedUser = :assignedUser')
                ->setParameter('assignedUser', $userFilter);
        } elseif ($userFilter === TicketingActionsInterface::USER_FILTER_UNASSIGNED) {
            $qb->andWhere('t.assignedUser IS NULL');
        }

        if ($groupFilter instanceof TicketGroup) {
            $qb
                ->andWhere('t.group = :group')
                ->setParameter('group', $groupFilter);
        }

        if ($lastActivityFilter && $lastActivityFilter !== TicketingActionsInterface::LAST_ACTIVITY_FILTER_ALL) {
            $qb
                ->andWhere('t.isLastActivityByClient = :isLastActivityByClient')
                ->setParameter(
                    'isLastActivityByClient',
                    $lastActivityFilter === TicketingActionsInterface::LAST_ACTIVITY_FILTER_CLIENT
                );
        }

        $tickets = $qb->getQuery()->getResult();
        $this->loadRelatedEntities($tickets);

        Arrays::sortByArray($tickets, $ids, 'id');

        return $tickets;
    }

    /**
     * @return Ticket[]
     */
    public function getTickets(TicketsRequest $ticketsRequest): array
    {
        $qb = $this->em->getRepository(Ticket::class)->getQueryBuilder($ticketsRequest)
            ->addOrderBy('t.lastActivity', 'DESC')
            ->addOrderBy('t.id', 'DESC');

        $tickets = $qb->getQuery()->getResult();

        $this->loadRelatedEntities($tickets);

        return $tickets;
    }

    /**
     * @param Ticket[] $tickets
     */
    private function loadRelatedEntities(array $tickets): void
    {
        $ids = array_map(
            function (Ticket $ticket) {
                return $ticket->getId();
            },
            $tickets
        );

        $ticketRepository = $this->em->getRepository(Ticket::class);
        $ticketRepository->loadRelatedEntities('activity', $ids);
        $ticketRepository->loadRelatedEntities('jobs', $ids);

        $commentIds = [];
        foreach ($tickets as $ticket) {
            foreach ($ticket->getActivity() as $activity) {
                if ($activity instanceof TicketComment) {
                    $commentIds[] = $activity->getId();
                }
            }
        }

        $ticketCommentRepository = $this->em->getRepository(TicketComment::class);
        $ticketCommentRepository->loadRelatedEntities('attachments', $commentIds);
        $ticketCommentRepository->loadRelatedEntities('mailAttachments', $commentIds);
    }

    /**
     * @return Ticket[]
     */
    public function getTicketsAPI(TicketCollectionRequest $ticketCollectionRequest): array
    {
        $qb = $this->em->getRepository(Ticket::class)
            ->createQueryBuilder('t')
            ->addGroupBy('t.id');

        if ($ticketCollectionRequest->user) {
            $qb
                ->andWhere('t.assignedUser = :user')
                ->setParameter('user', $ticketCollectionRequest->user);
        }

        if ($ticketCollectionRequest->client) {
            $qb
                ->andWhere('t.client = :client')
                ->setParameter('client', $ticketCollectionRequest->client);
        }

        if ($ticketCollectionRequest->ticketGroup) {
            $qb
                ->andWhere('t.group = :group')
                ->setParameter('group', $ticketCollectionRequest->ticketGroup);
        }

        if ($ticketCollectionRequest->startDate) {
            $qb
                ->andWhere('t.createdAt >= :startDate')
                ->setParameter('startDate', $ticketCollectionRequest->startDate, UtcDateTimeType::NAME);
        }

        if ($ticketCollectionRequest->endDate) {
            $qb
                ->andWhere('t.createdAt <= :endDate')
                ->setParameter('endDate', $ticketCollectionRequest->endDate, UtcDateTimeType::NAME);
        }

        if ($ticketCollectionRequest->statuses) {
            $qb
                ->andWhere('t.status IN (:statuses)')
                ->setParameter('statuses', $ticketCollectionRequest->statuses);
        }

        if ($ticketCollectionRequest->public !== null) {
            $qb
                ->andWhere('t.public = :public')
                ->setParameter('public', $ticketCollectionRequest->public);
        }

        if ($ticketCollectionRequest->limit) {
            $qb->setMaxResults($ticketCollectionRequest->limit);
        }

        if ($ticketCollectionRequest->offset) {
            $qb->setFirstResult($ticketCollectionRequest->offset);
        }

        if ($ticketCollectionRequest->filterNullRelations) {
            foreach ($ticketCollectionRequest->filterNullRelations as $filterNullRelation) {
                $qb->andWhere(sprintf('t.%s IS NULL', $filterNullRelation));
            }
        }

        if ($ticketCollectionRequest->order) {
            $ticketCollectionRequest->order = 't.' . $ticketCollectionRequest->order;
        }

        $qb->addOrderBy(
            $ticketCollectionRequest->order ?: 't.createdAt',
            $ticketCollectionRequest->direction ?: 'DESC'
        );

        $tickets = $qb->getQuery()->getResult();

        $this->loadRelatedEntities($tickets);

        return $tickets;
    }

    public function getAllPublicAttachmentPathsForClient(Client $client): array
    {
        $request = new TicketCollectionRequest();
        $request->client = $client;
        $request->order = 'id';
        $request->public = true;

        $tickets = $this->getTicketsAPI($request);

        $paths = [];
        $filesystem = new Filesystem();

        foreach ($tickets as $ticket) {
            $paths[$ticket->getId()] = [];

            foreach ($ticket->getActivity() as $activity) {
                if (
                    ! $activity instanceof TicketComment
                    || ! $activity->isPublic()
                    || $activity->getAttachments()->isEmpty()
                ) {
                    continue;
                }

                foreach ($activity->getAttachments() as $attachment) {
                    $path = $this->commentAttachmentFileManager->getFilePath($attachment);
                    if (! $filesystem->exists($path)) {
                        continue;
                    }

                    $paths[$ticket->getId()][] = new ExportPathData(
                        $attachment->getOriginalFilename(),
                        $path
                    );
                }
            }
        }

        return $paths;
    }
}
