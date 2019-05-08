<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Mapper;

use ApiBundle\Map\AbstractMap;
use ApiBundle\Map\CurrencyMap;
use AppBundle\Entity\Currency;

final class CurrencyMapper extends AbstractMapper
{
    protected function getMapClassName(): string
    {
        return CurrencyMap::class;
    }

    protected function getEntityClassName(): string
    {
        return Currency::class;
    }

    /**
     * @param Currency $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        $this->mapField($entity, $map, 'name');
        $this->mapField($entity, $map, 'code');
        $this->mapField($entity, $map, 'symbol');
    }

    /**
     * @param Currency $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'name', $entity->getName());
        $this->reflectField($map, 'code', $entity->getCode());
        $this->reflectField($map, 'symbol', $entity->getSymbol());
    }
}
