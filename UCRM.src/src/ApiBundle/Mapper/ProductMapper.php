<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Mapper;

use ApiBundle\Map\AbstractMap;
use ApiBundle\Map\ProductMap;
use AppBundle\Entity\Product;
use AppBundle\Entity\Tax;

class ProductMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return ProductMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return Product::class;
    }

    /**
     * @param Product $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        $this->mapField($entity, $map, 'name');
        $this->mapField($entity, $map, 'invoiceLabel');
        $this->mapField($entity, $map, 'unit');
        $this->mapField($entity, $map, 'price');
        $this->mapField($entity, $map, 'taxable');
        $this->mapField($entity, $map, 'tax', 'taxId', Tax::class);
    }

    /**
     * @param Product $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'name', $entity->getName());
        $this->reflectField($map, 'invoiceLabel', $entity->getInvoiceLabel());
        $this->reflectField($map, 'unit', $entity->getUnit());
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
