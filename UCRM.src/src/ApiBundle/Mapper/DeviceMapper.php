<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Mapper;

use ApiBundle\Exception\UnexpectedTypeException;
use ApiBundle\Map\AbstractMap;
use ApiBundle\Map\DeviceMap;
use AppBundle\Entity\Device;
use AppBundle\Entity\Site;
use AppBundle\Entity\Vendor;

class DeviceMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return DeviceMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return Device::class;
    }

    /**
     * @param Device $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        if (! $map instanceof DeviceMap) {
            throw new UnexpectedTypeException($map, DeviceMap::class);
        }

        $this->mapField($entity, $map, 'name');
        $this->mapField($entity, $map, 'vendor', 'vendorId', Vendor::class);
        $this->mapField($entity, $map, 'snmpCommunity');
        $this->mapField($entity, $map, 'notes');
        $this->mapField($entity, $map, 'site', 'siteId', Site::class);
        $this->mapField($entity, $map, 'isGateway');
        $this->mapField($entity, $map, 'isSuspendEnabled');
        $this->mapField($entity, $map, 'loginUsername');
        $this->mapField($entity, $map, 'loginPassword');
        $this->mapField($entity, $map, 'sshPort');
        $this->mapField($entity, $map, 'osVersion');
        $this->mapField($entity, $map, 'sendPingNotifications');
        $this->mapField($entity, $map, 'pingNotificationUser', 'pingNotificationUserId');
        $this->mapField($entity, $map, 'createSignalStatistics');

        if ($map->parentIds !== null) {
            $deviceRepository = $this->entityManager->getRepository(Device::class);
            foreach ($map->parentIds as $parentId) {
                $parent = $deviceRepository->find($parentId);
                if ($parent) {
                    $entity->addParent($parent);
                } else {
                    $this->errorCollector->add('parentIds', sprintf('Device with id %d not found.', $parentId));
                }
            }
        }
    }

    /**
     * @param Device $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        /** @var DeviceMap $map */
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'name', $entity->getName());
        $this->reflectField($map, 'vendorId', $entity->getVendor(), 'id');
        $this->reflectField($map, 'snmpCommunity', $entity->getSnmpCommunity());
        $this->reflectField($map, 'notes', $entity->getNotes());
        $this->reflectField($map, 'siteId', $entity->getSite(), 'id');
        $this->reflectField($map, 'isGateway', $entity->isGateway());
        $this->reflectField($map, 'isSuspendEnabled', $entity->isSuspendEnabled());
        $this->reflectField($map, 'loginUsername', $entity->getLoginUsername());
        $this->reflectField($map, 'sshPort', $entity->getSshPort());
        $this->reflectField($map, 'osVersion', $entity->getOsVersion());
        $this->reflectField($map, 'sendPingNotifications', $entity->isSendPingNotifications());
        $this->reflectField($map, 'pingNotificationUserId', $entity->getPingNotificationUser(), 'id');
        $this->reflectField($map, 'createSignalStatistics', $entity->getCreateSignalStatistics());
        $this->reflectField($map, 'modelName', $entity->getModelName());

        foreach ($entity->getParents() as $parent) {
            $map->parentIds[] = $parent->getId();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsDifference(): array
    {
        return [
            'vendor' => 'vendorId',
            'site' => 'siteId',
            'pingNotificationUser' => 'pingNotificationUserId',
            'parents' => 'parentIds',
        ];
    }
}
