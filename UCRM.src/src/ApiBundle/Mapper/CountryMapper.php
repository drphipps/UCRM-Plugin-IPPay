<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Mapper;

use ApiBundle\Map\AbstractMap;
use ApiBundle\Map\CountryMap;
use AppBundle\Entity\Country;

class CountryMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return CountryMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return Country::class;
    }

    /**
     * @param Country $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        $this->mapField($entity, $map, 'name');
        $this->mapField($entity, $map, 'code');
    }

    /**
     * @param Country $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'name', $entity->getName());
        $this->reflectField($map, 'code', $entity->getCode());
    }
}
