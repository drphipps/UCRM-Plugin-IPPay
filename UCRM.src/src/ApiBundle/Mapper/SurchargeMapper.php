<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Mapper;

use ApiBundle\Map\AbstractMap;
use ApiBundle\Map\SurchargeMap;
use AppBundle\Entity\Surcharge;
use AppBundle\Entity\Tax;

class SurchargeMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return SurchargeMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return Surcharge::class;
    }

    /**
     * @param Surcharge $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        $this->mapField($entity, $map, 'name');
        $this->mapField($entity, $map, 'invoiceLabel');
        $this->mapField($entity, $map, 'price');
        $this->mapField($entity, $map, 'taxable');
        $this->mapField($entity, $map, 'tax', 'taxId', Tax::class);
    }

    /**
     * @param Surcharge $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'name', $entity->getName());
        $this->reflectField($map, 'invoiceLabel', $entity->getInvoiceLabel());
        $this->reflectField($map, 'price', $entity->getPrice());
        $this->reflectField($map, 'taxable', $entity->getTaxable());
        $this->reflectField($map, 'taxId', $entity->getTax(), 'id');
    }

    public function getFieldsDifference(): array
    {
        return [
            'tax' => 'taxId',
        ];
    }
}
