<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\DataProvider;

use AppBundle\Database\UtcDateTimeType;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Entity\JobComment;

class JobCommentDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @return JobComment[]
     */
    public function getAllJobComments(
        ?Job $job,
        ?User $user,
        ?\DateTimeInterface $startDate,
        ?\DateTimeInterface $endDate,
        ?User $assignedUser
    ): array {
        $qb = $this->em->getRepository(JobComment::class)
            ->createQueryBuilder('jc')
            ->addOrderBy('jc.createdDate', 'ASC')
            ->addOrderBy('jc.id', 'ASC');

        if ($job) {
            $qb
                ->andWhere('jc.job = :job')
                ->setParameter('job', $job);
        }

        if ($user) {
            $qb
                ->andWhere('jc.user = :user')
                ->setParameter('user', $user);
        }

        if ($startDate) {
            $qb
                ->andWhere('jc.createdDate >= :startDate')
                ->setParameter('startDate', $startDate, UtcDateTimeType::NAME);
        }

        if ($endDate) {
            $qb
                ->andWhere('jc.createdDate <= :endDate')
                ->setParameter('endDate', $endDate, UtcDateTimeType::NAME);
        }

        if ($assignedUser) {
            $qb
                ->innerJoin('jc.job', 'j')
                ->andWhere('j.assignedUser = :assignedUser')
                ->setParameter('assignedUser', $assignedUser);
        }

        return $qb->getQuery()->getResult();
    }
}
