<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Repository;

use AppBundle\Entity\HeaderNotificationStatus;
use AppBundle\Entity\User;

class HeaderNotificationStatusRepository extends BaseRepository
{
    /**
     * @return HeaderNotificationStatus[]|array
     */
    public function getByUser(User $user, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('hns')
            ->addSelect('hn')
            ->join('hns.headerNotification', 'hn')
            ->where('hns.user = :user')
            ->orderBy('hn.createdDate', 'DESC')
            ->addOrderBy('hn.id', 'DESC')
            ->setParameter('user', $user);

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function getLastUnreadTimestamp(User $user): ?int
    {
        $date = $this->createQueryBuilder('hns')
            ->select('hn.createdDate')
            ->join('hns.headerNotification', 'hn')
            ->where('hns.user = :user')
            ->andWhere('hns.read = false')
            ->orderBy('hn.createdDate', 'DESC')
            ->addOrderBy('hn.id', 'DESC')
            ->setParameter('user', $user)
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        return $date ? $date['createdDate']->getTimestamp() : null;
    }

    public function getUnreadCount(User $user): int
    {
        return $this->createQueryBuilder('hns')
            ->select('COUNT(hns.id)')
            ->where('hns.user = :user')
            ->andWhere('hns.read = FALSE')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
