<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Repository;

use AppBundle\Database\UtcDateTimeType;
use AppBundle\Entity\Client;
use AppBundle\Entity\User;
use AppBundle\Repository\BaseRepository;
use AppBundle\Util\DateTimeFactory;
use Doctrine\ORM\QueryBuilder;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Request\JobCollectionRequest;

class JobRepository extends BaseRepository
{
    /**
     * @return Job[]
     */
    public function getByDateRange(\DateTimeImmutable $start, \DateTimeImmutable $end, ?User $user = null): array
    {
        $qb = $this->createQueryBuilder('j')
            ->select('j, u')
            ->innerJoin('j.assignedUser', 'u')
            ->andWhere(
                '
                    (
                        j.date >= :start
                    ) OR (
                        j.duration IS NOT NULL
                        AND
                        interval_add(j.date, \'minute\', j.duration) >= :start
                    )
                '
            )
            ->andWhere(
                '
                    (
                        j.date < :end
                    ) OR (
                        j.duration IS NOT NULL
                        AND
                        interval_add(j.date, \'minute\', j.duration) < :end
                    )
                '
            )
            ->setParameter('start', DateTimeFactory::createFromInterface($start), UtcDateTimeType::NAME)
            ->setParameter('end', DateTimeFactory::createFromInterface($end), UtcDateTimeType::NAME);

        if ($user) {
            $qb->andWhere('j.assignedUser = :user')
                ->setParameter('user', $user);
        }

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * @return Job[]
     */
    public function getByIds(array $ids): array
    {
        if (! $ids) {
            return [];
        }

        $qb = $this->createQueryBuilder('j')
            ->select('j')
            ->andWhere('j.id IN (:ids)')
            ->setParameter('ids', $ids);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Job[]
     */
    public function getByUser(User $user, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('j')
            ->select('j, u')
            ->innerJoin('j.assignedUser', 'u')
            ->andWhere('j.assignedUser = :user')
            ->andWhere('j.date IS NOT NULL')
            ->andWhere('j.status != :closed')
            ->orderBy('j.date')
            ->addOrderBy('j.title')
            ->setParameter('user', $user)
            ->setParameter('closed', Job::STATUS_CLOSED);

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Job[]
     */
    public function getByUserByDate(User $user, ?int $limit = null): array
    {
        return $this->convertToDateAssoc($this->getByUser($user, $limit));
    }

    /**
     * @return Job[]
     */
    public function getByClientByDate(
        Client $client,
        ?int $limit = null,
        ?User $user = null,
        ?bool $public = null
    ): array {
        return $this->convertToDateAssoc($this->getByClient($client, $limit, $user, $public));
    }

    /**
     * @return Job[]
     */
    public function getQueue(?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('j')
            ->select('j')
            ->andWhere('j.date IS NULL')
            ->andWhere('j.status != :closed')
            ->orderBy('j.id', 'DESC')
            ->setParameter('closed', Job::STATUS_CLOSED);

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function hasQueue(): bool
    {
        return (bool) $this->createQueryBuilder('j')
            ->select('1')
            ->andWhere('j.date IS NULL')
            ->andWhere('j.status != :closed')
            ->setParameter('closed', Job::STATUS_CLOSED)
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
    }

    public function getCount(): int
    {
        return (int) $this->createQueryBuilder('j')
            ->select('COUNT(j)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getQueryBuilder(JobCollectionRequest $jobRequest): QueryBuilder
    {
        $qb = $this->createQueryBuilder('j');

        if ($jobRequest->user) {
            $qb
                ->andWhere('j.assignedUser = :user')
                ->setParameter('user', $jobRequest->user);
        }

        if ($jobRequest->client) {
            $qb
                ->andWhere('j.client = :client')
                ->setParameter('client', $jobRequest->client);
        }

        if ($jobRequest->ticket) {
            $qb
                ->innerJoin('j.tickets', 't')
                ->andWhere('t.id = :ticket')
                ->setParameter('ticket', $jobRequest->ticket);
        }

        if ($jobRequest->startDate) {
            $qb
                ->andWhere('j.date >= :startDate')
                ->setParameter('startDate', $jobRequest->startDate, UtcDateTimeType::NAME);
        }

        if ($jobRequest->endDate) {
            $qb
                ->andWhere('j.date <= :endDate')
                ->setParameter('endDate', $jobRequest->endDate, UtcDateTimeType::NAME);
        }

        if ($jobRequest->statuses) {
            $qb
                ->andWhere('j.status IN (:statuses)')
                ->setParameter('statuses', $jobRequest->statuses);
        }

        if ($jobRequest->filterNullRelations) {
            foreach ($jobRequest->filterNullRelations as $filterNullRelation) {
                $qb->andWhere(sprintf('j.%s IS NULL', $filterNullRelation));
            }
        }

        if ($jobRequest->public !== null) {
            $qb->andWhere('j.public = :public');
            $qb->setParameter('public', $jobRequest->public);
        }

        if ($jobRequest->limit) {
            $qb->setMaxResults($jobRequest->limit);
        }

        if ($jobRequest->offset) {
            $qb->setFirstResult($jobRequest->offset);
        }

        return $qb;
    }

    /**
     * @return Job[]
     */
    private function getByClient(Client $client, ?int $limit = null, ?User $user = null, ?bool $public = null): array
    {
        $qb = $this->createQueryBuilder('j')
            ->select('j, c')
            ->innerJoin('j.client', 'c')
            ->andWhere('j.client = :client')
            ->andWhere('j.status != :closed')
            ->orderBy('j.date')
            ->addOrderBy('j.title')
            ->setParameter('client', $client)
            ->setParameter('closed', Job::STATUS_CLOSED);

        if ($user) {
            $qb
                ->andWhere('j.assignedUser = :user')
                ->setParameter('user', $user);
        }

        if ($public !== null) {
            $qb->andWhere('j.public = :public');
            $qb->setParameter('public', $public);
        }

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function convertToDateAssoc(array $jobs): array
    {
        if (! $jobs) {
            return [];
        }

        $items = [];
        foreach ($jobs as $job) {
            $key = $job->getDate() ? $job->getDate()->format('Y-m-d') : 'queue';
            $items[$key]['date'] = $job->getDate();
            $items[$key]['jobs'][] = $job;
        }

        uksort(
            $items,
            function (string $a, string $b) {
                return $a === 'queue' ? -1 : ($b === 'queue' ? 1 : 0);
            }
        );

        return $items;
    }
}
