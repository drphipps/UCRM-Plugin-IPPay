<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Mapper;

use ApiBundle\Map\AbstractMap;
use ApiBundle\Map\CustomAttributeMap;
use AppBundle\Entity\CustomAttribute;
use AppBundle\Util\Strings;

class CustomAttributeMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return CustomAttributeMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return CustomAttribute::class;
    }

    /**
     * @param AbstractMap|CustomAttributeMap $map
     * @param CustomAttribute                $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        if ($entity->getAttributeType() === null) {
            $this->mapField($entity, $map, 'attributeType');
        } elseif ($map->attributeType !== $entity->getAttributeType()) {
            $this->errorCollector->add('attributeType', 'Changing type of an existing custom attribute is not allowed.');
        }

        $this->mapField($entity, $map, 'name');
        $entity->setKey(Strings::slugifyCamelCase($entity->getName()));
    }

    /**
     * @param CustomAttribute                $entity
     * @param AbstractMap|CustomAttributeMap $map
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'name', $entity->getName());
        $this->reflectField($map, 'key', $entity->getKey());
        $this->reflectField($map, 'attributeType', $entity->getAttributeType());
    }
}
