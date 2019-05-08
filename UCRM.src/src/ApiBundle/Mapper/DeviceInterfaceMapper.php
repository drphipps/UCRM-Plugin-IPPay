<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Mapper;

use ApiBundle\Map\AbstractMap;
use ApiBundle\Map\DeviceInterfaceMap;
use AppBundle\Entity\DeviceInterface;

class DeviceInterfaceMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return DeviceInterfaceMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return DeviceInterface::class;
    }

    /**
     * @param DeviceInterface $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        $this->mapField($entity, $map, 'name');
        $this->mapField($entity, $map, 'type');
        $this->mapField($entity, $map, 'macAddress');
        $this->mapField($entity, $map, 'allowClientConnection');
        $this->mapField($entity, $map, 'notes');
        $this->mapField($entity, $map, 'enabled');
        $this->mapField($entity, $map, 'ssid');
        $this->mapField($entity, $map, 'frequency');
        $this->mapField($entity, $map, 'polarization');
        $this->mapField($entity, $map, 'encryptionType');
        $this->mapField($entity, $map, 'encryptionKeyWpa');
        $this->mapField($entity, $map, 'encryptionKeyWpa2');
    }

    /**
     * @param DeviceInterface $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        /** @var DeviceInterfaceMap $map */
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'deviceId', $entity->getDevice(), 'id');
        $this->reflectField($map, 'name', $entity->getName());
        $this->reflectField($map, 'type', $entity->getType());
        $this->reflectField($map, 'macAddress', $entity->getMacAddress());
        $this->reflectField($map, 'allowClientConnection', $entity->getAllowClientConnection());
        $this->reflectField($map, 'notes', $entity->getNotes());
        $this->reflectField($map, 'enabled', $entity->getEnabled());
        $this->reflectField($map, 'ssid', $entity->getSsid());
        $this->reflectField($map, 'frequency', $entity->getFrequency());
        $this->reflectField($map, 'polarization', $entity->getPolarization());
        $this->reflectField($map, 'encryptionType', $entity->getEncryptionType());
        $this->reflectField($map, 'encryptionKeyWpa', $entity->getEncryptionKeyWpa());
        $this->reflectField($map, 'encryptionKeyWpa2', $entity->getEncryptionKeyWpa2());

        $map->ipRanges = [];
        foreach ($entity->getInterfaceIps() as $interfaceIp) {
            $map->ipRanges[] = $interfaceIp->getIpRange()->getRange();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsDifference(): array
    {
        return [
            'device' => 'deviceId',
        ];
    }
}
