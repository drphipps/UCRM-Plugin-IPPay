<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Mapper;

use ApiBundle\Exception\UnexpectedTypeException;
use ApiBundle\Map\AbstractMap;
use ApiBundle\Map\ClientTagMap;
use AppBundle\Entity\ClientTag;

class ClientTagMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return ClientTagMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return ClientTag::class;
    }

    /**
     * @param ClientTag $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        if (! $map instanceof ClientTagMap) {
            throw new UnexpectedTypeException($map, ClientTagMap::class);
        }

        $this->mapField($entity, $map, 'name');
        $this->mapField($entity, $map, 'colorBackground');
        $this->mapField($entity, $map, 'colorText');
    }

    /**
     * @param ClientTag $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'name', $entity->getName());
        $this->reflectField($map, 'colorBackground', $entity->getColorBackground());
        $this->reflectField($map, 'colorText', $entity->getColorText());
    }
}
