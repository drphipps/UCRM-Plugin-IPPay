<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Mapper;

use ApiBundle\Map\AbstractMap;
use ApiBundle\Map\ServiceSurchargeMap;
use AppBundle\Entity\ServiceSurcharge;
use AppBundle\Entity\Surcharge;

class ServiceSurchargeMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return ServiceSurchargeMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return ServiceSurcharge::class;
    }

    /**
     * @param ServiceSurcharge $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        $this->mapField($entity, $map, 'surcharge', 'surchargeId', Surcharge::class);
        $this->mapField($entity, $map, 'invoiceLabel');
        $this->mapField($entity, $map, 'price');
        $this->mapField($entity, $map, 'taxable');
    }

    /**
     * @param ServiceSurcharge $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'serviceId', $entity->getService(), 'id');
        $this->reflectField($map, 'surchargeId', $entity->getSurcharge(), 'id');
        $this->reflectField($map, 'invoiceLabel', $entity->getInvoiceLabel());
        $this->reflectField($map, 'price', $entity->getPrice());
        $this->reflectField($map, 'taxable', $entity->getTaxable());
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsDifference(): array
    {
        return [
            'service' => 'serviceId',
            'surcharge' => 'surchargeId',
        ];
    }
}
