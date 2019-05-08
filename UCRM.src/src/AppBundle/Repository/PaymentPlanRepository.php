<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Repository;

use AppBundle\Database\UtcDateTimeType;
use AppBundle\Entity\Organization;

class PaymentPlanRepository extends BaseRepository
{
    public function findSubscriptionsForNextPayment(
        string $provider,
        Organization $organization,
        \DateTime $today
    ): array {
        return $this->createQueryBuilder('pp')
            ->innerJoin('pp.client', 'c')
            ->andWhere('c.organization = :organization')
            ->andWhere('pp.active = true')
            ->andWhere('pp.nextPaymentDate IS NOT NULL')
            ->andWhere('pp.nextPaymentDate <= :today')
            ->andWhere('pp.provider = :provider')
            ->andWhere('pp.providerSubscriptionId IS NOT NULL')
            ->setParameter('organization', $organization)
            ->setParameter('today', $today, UtcDateTimeType::NAME)
            ->setParameter('provider', $provider)
            ->getQuery()
            ->getResult();
    }
}
