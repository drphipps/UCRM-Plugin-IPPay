<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Facade;

use AppBundle\Entity\HeaderNotificationStatus;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

class HeaderNotificationFacade
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getGridModel(User $user): QueryBuilder
    {
        return $this->em->getRepository(HeaderNotificationStatus::class)
            ->createQueryBuilder('hns')
            ->addSelect('hn')
            ->join('hns.headerNotification', 'hn')
            ->andWhere('hns.user = :user')
            ->addOrderBy('hn.createdDate', 'DESC')
            ->addOrderBy('hn.id', 'DESC')
            ->setParameter('user', $user);
    }

    public function markAllAsRead(User $user)
    {
        $this->em->createQueryBuilder()
            ->update(HeaderNotificationStatus::class, 'hns')
            ->set('hns.read', 'true')
            ->where('hns.user = :user')
            ->setParameter('user', $user)
            ->getQuery()->execute();
    }

    public function markAsRead(HeaderNotificationStatus $notificationStatus)
    {
        $this->em->createQueryBuilder()
            ->update(HeaderNotificationStatus::class, 'hns')
            ->set('hns.read', 'true')
            ->where('hns.id = :notificationStatus')
            ->setParameter('notificationStatus', $notificationStatus)
            ->getQuery()->execute();
    }
}
