<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Facade;

use AppBundle\Entity\ServiceStopReason;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

class ServiceStopReasonFacade
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getGridModel(): QueryBuilder
    {
        return $this->em
            ->getRepository(ServiceStopReason::class)
            ->createQueryBuilder('ssr')
            ->orderBy('ssr.id');
    }
}
