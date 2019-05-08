<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Mapper;

use ApiBundle\Map\AbstractMap;
use ApiBundle\Map\SiteMap;
use AppBundle\Entity\Site;

class SiteMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return SiteMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return Site::class;
    }

    /**
     * @param Site $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        $this->mapField($entity, $map, 'name');
        $this->mapField($entity, $map, 'address');
        $this->mapField($entity, $map, 'gpsLat');
        $this->mapField($entity, $map, 'gpsLon');
        $this->mapField($entity, $map, 'contactInfo');
        $this->mapField($entity, $map, 'notes');
    }

    /**
     * @param Site $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'name', $entity->getName());
        $this->reflectField($map, 'address', $entity->getAddress());
        $this->reflectField($map, 'gpsLat', $entity->getGpsLat());
        $this->reflectField($map, 'gpsLon', $entity->getGpsLon());
        $this->reflectField($map, 'contactInfo', $entity->getContactInfo());
        $this->reflectField($map, 'notes', $entity->getNotes());
    }
}
