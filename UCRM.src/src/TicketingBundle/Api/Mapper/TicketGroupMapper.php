<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace TicketingBundle\Api\Mapper;

use ApiBundle\Exception\UnexpectedTypeException;
use ApiBundle\Map\AbstractMap;
use ApiBundle\Mapper\AbstractMapper;
use TicketingBundle\Api\Map\TicketGroupMap;
use TicketingBundle\Entity\TicketGroup;

final class TicketGroupMapper extends AbstractMapper
{
    protected function getMapClassName(): string
    {
        return TicketGroupMap::class;
    }

    protected function getEntityClassName(): string
    {
        return TicketGroup::class;
    }

    /**
     * @param TicketGroup $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        if (! $map instanceof TicketGroupMap) {
            throw new UnexpectedTypeException($map, TicketGroupMap::class);
        }

        $this->mapField($entity, $map, 'name');
    }

    /**
     * @param TicketGroup $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        if (! $map instanceof TicketGroupMap) {
            throw new UnexpectedTypeException($map, TicketGroupMap::class);
        }

        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'name', $entity->getName());
    }
}
