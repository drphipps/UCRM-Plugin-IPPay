<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Facade;

use AppBundle\Entity\DeviceLog;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

class DeviceLogFacade
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getGridModel(array $deviceIds = null): QueryBuilder
    {
        $qb = $this->em->getRepository(DeviceLog::class)
            ->createQueryBuilder('dl')
            ->select('dl, d, s')
            ->join('dl.device', 'd')
            ->join('d.site', 's')
            ->andWhere('d.deletedAt IS NULL')
            ->addOrderBy('dl.createdDate', 'DESC')
            ->addOrderBy('dl.id', 'DESC')
            ->addGroupBy('dl.id, d.id, s.id');

        if ($deviceIds) {
            $qb->andWhere('dl.device IN (:deviceIds)')
                ->setParameter('deviceIds', $deviceIds);
        }

        return $qb;
    }
}
