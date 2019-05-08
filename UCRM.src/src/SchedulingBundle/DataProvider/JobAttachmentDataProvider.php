<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\DataProvider;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Entity\JobAttachment;

class JobAttachmentDataProvider
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
     * @return JobAttachment[]
     */
    public function getAllJobAttachments(?Job $job = null, ?User $assignedUser = null): array
    {
        $qb = $this->em->getRepository(JobAttachment::class)
            ->createQueryBuilder('ja')
            ->addOrderBy('ja.id', 'ASC');

        if ($job) {
            $qb
                ->andWhere('ja.job = :job')
                ->setParameter('job', $job);
        }

        if ($assignedUser) {
            $qb
                ->innerJoin('ja.job', 'j')
                ->andWhere('j.assignedUser = :assignedUser')
                ->setParameter('assignedUser', $assignedUser);
        }

        return $qb->getQuery()->getResult();
    }
}
