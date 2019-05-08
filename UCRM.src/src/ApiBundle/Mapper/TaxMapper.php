<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Mapper;

use ApiBundle\Map\AbstractMap;
use ApiBundle\Map\TaxMap;
use AppBundle\Entity\Tax;

class TaxMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return TaxMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return Tax::class;
    }

    /**
     * @param Tax $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        $this->mapField($entity, $map, 'name');
        $this->mapField($entity, $map, 'agencyName');
        $this->mapField($entity, $map, 'rate');
        $this->mapField($entity, $map, 'selected');
    }

    /**
     * @param Tax $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'name', $entity->getName());
        $this->reflectField($map, 'agencyName', $entity->getAgencyName());
        $this->reflectField($map, 'rate', $entity->getRate());
        $this->reflectField($map, 'selected', $entity->getSelected());
    }
}
