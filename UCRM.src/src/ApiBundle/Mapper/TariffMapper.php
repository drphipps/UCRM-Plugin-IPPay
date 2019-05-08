<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Mapper;

use ApiBundle\Exception\UnexpectedTypeException;
use ApiBundle\Map\AbstractMap;
use ApiBundle\Map\TariffMap;
use ApiBundle\Map\TariffPeriodMap;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Tariff;
use AppBundle\Entity\TariffPeriod;
use AppBundle\Entity\Tax;

class TariffMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return TariffMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return Tariff::class;
    }

    /**
     * @param Tariff $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        if (! $map instanceof TariffMap) {
            throw new UnexpectedTypeException($map, TariffMap::class);
        }

        $this->mapField($entity, $map, 'name');
        $this->mapField($entity, $map, 'invoiceLabel');
        $this->mapField($entity, $map, 'downloadBurst');
        $this->mapField($entity, $map, 'uploadBurst');
        $this->mapField($entity, $map, 'downloadSpeed');
        $this->mapField($entity, $map, 'uploadSpeed');
        $this->mapField($entity, $map, 'organization', 'organizationId', Organization::class);
        $this->mapField($entity, $map, 'taxable');
        $this->mapField($entity, $map, 'tax', 'taxId', Tax::class);
        $this->mapField($entity, $map, 'dataUsageLimit');

        if (null !== $map->periods) {
            /** @var TariffPeriodMap $periodMap */
            foreach ($map->periods as $periodMap) {
                if (null === $periodMap->period) {
                    $this->errorCollector->add('periods[].period', 'This field should not be blank.');
                } else {
                    $period = $entity->getPeriodByPeriod($periodMap->period);
                    if ($period) {
                        $this->mapField($period, $periodMap, 'price');
                        $this->mapField($period, $periodMap, 'enabled');
                    } else {
                        $this->errorCollector->add(
                            'periods[].period',
                            sprintf('This value is not allowed: %d.', $periodMap->period)
                        );
                    }
                }
            }
        }
    }

    /**
     * @param Tariff $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        /** @var TariffMap $map */
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'name', $entity->getName());
        $this->reflectField($map, 'invoiceLabel', $entity->getInvoiceLabel());
        $this->reflectField($map, 'downloadBurst', $entity->getDownloadBurst());
        $this->reflectField($map, 'uploadBurst', $entity->getUploadBurst());
        $this->reflectField($map, 'downloadSpeed', $entity->getDownloadSpeed());
        $this->reflectField($map, 'uploadSpeed', $entity->getUploadSpeed());
        $this->reflectField($map, 'organizationId', $entity->getOrganization(), 'id');
        $this->reflectField($map, 'taxable', $entity->getTaxable());
        $this->reflectField($map, 'taxId', $entity->getTax(), 'id');
        $this->reflectField($map, 'dataUsageLimit', $entity->getDataUsageLimit(), 'id');

        foreach ($entity->getPeriods() as $period) {
            $periodMap = new TariffPeriodMap();
            $this->reflectField($periodMap, 'id', $period->getId());
            $this->reflectField($periodMap, 'price', $period->getPrice());
            $this->reflectField($periodMap, 'period', $period->getPeriod());
            $this->reflectField($periodMap, 'enabled', $period->isEnabled());

            $map->periods[] = $periodMap;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsDifference(): array
    {
        $return = [
            'organization' => 'organizationId',
            'tax' => 'taxId',
        ];

        foreach (array_keys(TariffPeriod::PERIODS) as $key) {
            $return[sprintf('periods[%d].price', $key)] = 'periods[].price';
            $return[sprintf('periods[%d].period', $key)] = 'periods[].period';
            $return[sprintf('periods[%d].enabled', $key)] = 'periods[].enabled';
        }

        return $return;
    }
}
