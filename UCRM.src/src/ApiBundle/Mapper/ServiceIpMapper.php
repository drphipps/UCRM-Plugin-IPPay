<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Mapper;

use ApiBundle\Map\AbstractMap;
use ApiBundle\Map\ServiceIpMap;
use AppBundle\Entity\ServiceIp;

class ServiceIpMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return ServiceIpMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return ServiceIp::class;
    }

    /**
     * @param ServiceIp $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        $this->mapField($entity->getIpRange(), $map, 'rangeFromString', 'ipRange');
    }

    /**
     * @param ServiceIp $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'serviceDeviceId', $entity->getServiceDevice(), 'id');
        $this->reflectField($map, 'ipRange', $entity->getIpRange()->getRange());
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsDifference(): array
    {
        return [
            'ipRange.ipAddress' => 'ipRange',
            'rangeFromString' => 'ipRange',
            'serviceDevice' => 'serviceDeviceId',
        ];
    }
}
