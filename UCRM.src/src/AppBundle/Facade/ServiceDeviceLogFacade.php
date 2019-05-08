<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Facade;

use AppBundle\Entity\ServiceDevice;
use AppBundle\Entity\ServiceDeviceLog;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

class ServiceDeviceLogFacade
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getGridModel(ServiceDevice $device): QueryBuilder
    {
        $qb = $this->em->getRepository(ServiceDeviceLog::class)->createQueryBuilder('dl');
        $qb->where('dl.serviceDevice = :deviceId');
        $qb->setParameter('deviceId', $device->getId());
        $qb->addOrderBy('dl_created_date', 'DESC');
        $qb->addOrderBy('dl_id', 'DESC');

        return $qb;
    }
}
