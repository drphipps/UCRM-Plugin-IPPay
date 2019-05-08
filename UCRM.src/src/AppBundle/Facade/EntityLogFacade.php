<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\EntityLog;
use AppBundle\Entity\Site;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class EntityLogFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getGridModel(?Site $site): QueryBuilder
    {
        $qb = $this->em->getRepository(EntityLog::class)
            ->createQueryBuilder('el')
            ->addSelect('u, c, o, cur')
            ->addSelect('u.fullName as u_fullname')
            ->leftJoin('el.user', 'u')
            ->leftJoin('el.client', 'c')
            ->leftJoin('c.organization', 'o')
            ->leftJoin('o.currency', 'cur')
            ->groupBy('el.id, u.id, c.id, o.id, cur.id')
            ->addOrderBy('el.createdDate', 'DESC')
            ->addOrderBy('el.id', 'DESC');

        if ($site) {
            $qb->andWhere('el.site = :siteId')
                ->setParameter(':siteId', $site->getId());
        }

        return $qb;
    }
}
