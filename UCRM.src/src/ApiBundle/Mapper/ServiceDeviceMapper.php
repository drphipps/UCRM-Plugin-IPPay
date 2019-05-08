<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Mapper;

use ApiBundle\Exception\UnexpectedTypeException;
use ApiBundle\Map\AbstractMap;
use ApiBundle\Map\ServiceDeviceMap;
use AppBundle\Entity\Device;
use AppBundle\Entity\DeviceInterface;
use AppBundle\Entity\ServiceDevice;
use AppBundle\Entity\ServiceIp;
use AppBundle\Entity\User;
use AppBundle\Entity\Vendor;

class ServiceDeviceMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return ServiceDeviceMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return ServiceDevice::class;
    }

    /**
     * @param ServiceDevice $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        if (! $map instanceof ServiceDeviceMap) {
            throw new UnexpectedTypeException($map, ServiceDeviceMap::class);
        }

        $this->mapField($entity, $map, 'interface', 'interfaceId', DeviceInterface::class);
        $this->mapField($entity, $map, 'vendor', 'vendorId', Vendor::class);
        $this->mapField($entity, $map, 'macAddress');
        $this->mapField($entity, $map, 'loginUsername');
        $this->mapField($entity, $map, 'loginPassword');
        $this->mapField($entity, $map, 'sshPort');
        $this->mapField($entity, $map, 'sendPingNotifications');
        $this->mapField(
            $entity,
            $map,
            'pingNotificationUser',
            'pingNotificationUserId',
            User::class,
            [
                'role' => User::ADMIN_ROLES,
            ]
        );
        $this->mapField($entity, $map, 'createPingStatistics');
        $this->mapField($entity, $map, 'createSignalStatistics');
        $this->mapField($entity, $map, 'qosEnabled');

        if (null !== $map->ipRange) {
            foreach ($map->ipRange as $ipRange) {
                $serviceIp = new ServiceIp();
                $serviceIp->getIpRange()->setRangeFromString($ipRange);
                $serviceIp->setServiceDevice($entity);
                $entity->addServiceIp($serviceIp);
            }
        }

        if (null !== $map->qosDeviceIds) {
            $deviceRepository = $this->entityManager->getRepository(Device::class);
            foreach ($map->qosDeviceIds as $deviceId) {
                $device = $deviceRepository->find($deviceId);
                if ($device) {
                    $entity->addQosDevice($device);
                } else {
                    $this->errorCollector->add('qosDeviceIds', sprintf('Device with id %d not found.', $deviceId));
                }
            }
        }
    }

    /**
     * @param ServiceDevice $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        /** @var ServiceDeviceMap $map */
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'serviceId', $entity->getService(), 'id');
        $this->reflectField($map, 'interfaceId', $entity->getInterface(), 'id');
        $this->reflectField($map, 'vendorId', $entity->getVendor(), 'id');
        $this->reflectField($map, 'macAddress', $entity->getMacAddress());
        $this->reflectField($map, 'loginUsername', $entity->getLoginUsername());
        $this->reflectField($map, 'sshPort', $entity->getSshPort());
        $this->reflectField($map, 'sendPingNotifications', $entity->isSendPingNotifications());
        $this->reflectField($map, 'pingNotificationUserId', $entity->getPingNotificationUser(), 'id');
        $this->reflectField($map, 'createPingStatistics', $entity->isCreatePingStatistics());
        $this->reflectField($map, 'createSignalStatistics', $entity->getCreateSignalStatistics());
        $this->reflectField($map, 'qosEnabled', $entity->getQosEnabled());

        foreach ($entity->getQosDevices() as $serviceIp) {
            $map->qosDeviceIds[] = $serviceIp->getId();
        }

        foreach ($entity->getServiceIps() as $serviceIp) {
            $map->ipRange[] = $serviceIp->getIpRange()->getRangeForView();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsDifference(): array
    {
        return [
            'service' => 'serviceId',
            'interface' => 'interfaceId',
            'vendor' => 'vendorId',
            'serviceIps' => 'ipRange',
            'qosDevices' => 'qosDeviceIds',
        ];
    }
}
