<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\DataProvider;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Entity\JobLogsView;

class JobLogsViewDataProvider
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getGridModel(?Job $job = null, array $filters = []): QueryBuilder
    {
        $qb = $this->em->getRepository(JobLogsView::class)
            ->createQueryBuilder('jlw')
            ->addOrderBy('jlw.createdDate', 'DESC');

        if ($job) {
            $qb->andWhere('jlw.jobId = :jobId')
                ->setParameter(':jobId', $job->getId());
        }

        foreach ($filters as $identifier => $value) {
            $qb->andWhere('jlw.' . $identifier . ' IN (' . ':' . $identifier . ')')
                ->setParameter(':' . $identifier, $value);
        }

        return $qb;
    }
}
