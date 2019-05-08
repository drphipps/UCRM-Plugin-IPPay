<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\DataProvider;

use AppBundle\Entity\User;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Request\JobCollectionRequest;
use TicketingBundle\Entity\Ticket;

class JobDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getGridModel(?User $user = null): QueryBuilder
    {
        $qb = $this->em->getRepository(Job::class)
            ->createQueryBuilder('j')
            ->addSelect('u.fullName as j_assigned_user')
            ->addSelect('client_full_name(c, cu) as j_client')
            ->addSelect('j.duration as j_duration')
            ->addSelect('j.date as j_date')
            ->leftJoin('j.assignedUser', 'u')
            ->leftJoin('j.client', 'c')
            ->leftJoin('c.user', 'cu');

        if ($user) {
            $qb->andWhere('j.assignedUser = :user')
                ->setParameter('user', $user);
        }

        return $qb;
    }

    /**
     * @return Job[]
     */
    public function getAllJobs(JobCollectionRequest $jobRequest): array
    {
        $qb = $this->em->getRepository(Job::class)->getQueryBuilder($jobRequest)
            ->addOrderBy('j.date', 'ASC')
            ->addOrderBy('j.id', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function getAllByIds(array $ids): array
    {
        $repository = $this->em->getRepository(Job::class);
        $criteria = Criteria::create();

        $criteria->andWhere(Criteria::expr()->in('id', $ids));

        $criteria->orderBy(
            [
                'date' => 'ASC',
                'id' => 'ASC',
            ]
        );

        return $repository->matching($criteria)->toArray();
    }

    public function getJobsByUser(User $user, array $jobIds): array
    {
        if (! $jobIds) {
            return [];
        }

        $qb = $this->em->getRepository(Job::class)
            ->createQueryBuilder('j')
            ->addOrderBy('j.date', 'ASC')
            ->addOrderBy('j.id', 'ASC')
            ->andWhere('j.assignedUser = :user')
            ->setParameter('user', $user)
            ->andWhere('j.id IN (:ids)')
            ->setParameter('ids', $jobIds);

        return $qb->getQuery()->getResult();
    }

    public function getJobsForTicket(Ticket $ticket, ?User $user): array
    {
        $jobRepository = $this->em->getRepository(Job::class);

        $qb = $jobRepository
            ->createQueryBuilder('j')
            ->andWhere(':ticket NOT MEMBER OF j.tickets')
            ->andWhere('j.status IN (:statuses)')
            ->addOrderBy('j.date', 'ASC')
            ->addOrderBy('j.id', 'ASC')
            ->setParameter('ticket', $ticket)
            ->setParameter('statuses', [Job::STATUS_OPEN, Job::STATUS_IN_PROGRESS]);

        if ($user) {
            $qb->andWhere('j.assignedUser = :user')
                ->setParameter('user', $user);
        }

        return $jobRepository->convertToDateAssoc($qb->getQuery()->getResult());
    }

    public function getById(int $jobId): ?Job
    {
        return $this->em->find(Job::class, $jobId);
    }
}
