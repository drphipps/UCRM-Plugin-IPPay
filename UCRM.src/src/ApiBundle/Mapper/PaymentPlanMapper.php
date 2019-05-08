<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Mapper;

use ApiBundle\Map\AbstractMap;
use ApiBundle\Map\PaymentMap;
use ApiBundle\Map\PaymentPlanMap;
use AppBundle\Entity\Client;
use AppBundle\Entity\Currency;
use AppBundle\Entity\PaymentPlan;

class PaymentPlanMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return PaymentPlanMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return PaymentPlan::class;
    }

    /**
     * @param PaymentPlan $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        /** @var PaymentPlanMap $map */
        $this->mapField($entity, $map, 'name');
        $this->mapField($entity, $map, 'provider');
        $this->mapField($entity, $map, 'providerPlanId');
        $this->mapField($entity, $map, 'providerSubscriptionId');
        $this->mapField($entity, $map, 'client', 'clientId', Client::class);
        $this->mapField($entity, $map, 'currency', 'currencyId', Currency::class);

        if ($map->currencyId) {
            $currency = $this->entityManager->find(Currency::class, $map->currencyId);
            $smallestUnitMultiplier = $currency ? 10 ** $currency->getFractionDigits() : 100;
            assert(is_int($smallestUnitMultiplier));
            $entity->setSmallestUnitMultiplier($smallestUnitMultiplier);
            $entity->setAmountInSmallestUnit((int) ($map->amount * $smallestUnitMultiplier));
        }

        $this->mapField($entity, $map, 'period');
        $this->mapField($entity, $map, 'startDate');
    }

    /**
     * @param PaymentPlan $entity
     * @param PaymentMap  $map
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'name', $entity->getName());
        $this->reflectField($map, 'provider', $entity->getProvider());
        $this->reflectField($map, 'providerPlanId', $entity->getProviderPlanId());
        $this->reflectField($map, 'providerSubscriptionId', $entity->getProviderSubscriptionId());
        $this->reflectField($map, 'clientId', $entity->getClient(), 'id');
        $this->reflectField($map, 'currencyId', $entity->getCurrency(), 'id');

        $map->amount = $entity->getAmountInSmallestUnit() / $entity->getSmallestUnitMultiplier();

        $this->reflectField($map, 'period', $entity->getPeriod());
        $this->reflectField($map, 'createdDate', $entity->getCreatedDate());
        $this->reflectField($map, 'canceledDate', $entity->getCanceledDate());
        $this->reflectField($map, 'startDate', $entity->getStartDate());
        $this->reflectField($map, 'nextPaymentDate', $entity->getNextPaymentDate());
        $this->reflectField($map, 'status', $entity->getStatus());
        $this->reflectField($map, 'active', $entity->isActive());
    }

    public function getFieldsDifference(): array
    {
        return [
            'client' => 'clientId',
            'currency' => 'currencyId',
        ];
    }
}
