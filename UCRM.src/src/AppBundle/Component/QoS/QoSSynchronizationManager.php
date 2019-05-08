<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\QoS;

use AppBundle\Entity\BaseDevice;
use AppBundle\Entity\Device;
use AppBundle\Entity\Option;
use AppBundle\Entity\ServiceDevice;
use AppBundle\Entity\ServiceIp;
use AppBundle\Entity\Tariff;
use AppBundle\Entity\Vendor;
use AppBundle\Service\Options;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;

class QoSSynchronizationManager
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Options
     */
    private $options;

    public function __construct(EntityManager $em, Options $options)
    {
        $this->em = $em;
        $this->options = $options;
    }

    /**
     * @deprecated use a subscriber instead
     */
    public function unsynchronizeDevice(BaseDevice $device, BaseDevice $oldDevice = null)
    {
        if (! $this->options->get(Option::QOS_ENABLED)) {
            return;
        }

        if (null !== $oldDevice && $oldDevice->getQosAttributes() === $device->getQosAttributes()) {
            return;
        }

        if ($this->options->get(Option::QOS_DESTINATION) === Option::QOS_DESTINATION_GATEWAY) {
            if (
                ($device instanceof Device && $device->isGateway())
                || ($oldDevice instanceof Device && $oldDevice->isGateway())
            ) {
                $device->setQosSynchronized(false);
            }

            if (
                $device instanceof ServiceDevice
                && (
                    $device->getQosServiceIps()
                    || (
                        $oldDevice instanceof ServiceDevice
                        && $oldDevice->getQosServiceIps() !== $device->getQosServiceIps()
                    )
                )
            ) {
                $this->markAllGatewaysUnsynchronized();
            }
        } else {
            $this->markTopParentsUnsynchronized($device);
            if ($oldDevice) {
                switch ($oldDevice->getQosEnabled()) {
                    case BaseDevice::QOS_THIS:
                        $device->setQosSynchronized(false);
                        break;
                    case BaseDevice::QOS_ANOTHER:
                        $this->markTopParentsUnsynchronized($oldDevice);
                        break;
                }
            }
        }
    }

    /**
     * @deprecated use a subscriber instead
     */
    public function unsynchronizeServiceIp(ServiceIp $serviceIp, ServiceIp $oldServiceIp = null)
    {
        if (! $this->options->get(Option::QOS_ENABLED)) {
            return;
        }

        if (null !== $oldServiceIp
            && $oldServiceIp->getIpRange()->getRangeForView() === $serviceIp->getIpRange()->getRangeForView()
        ) {
            return;
        }

        if ($this->options->get(Option::QOS_DESTINATION) === Option::QOS_DESTINATION_GATEWAY) {
            $this->markAllGatewaysUnsynchronized();
        } else {
            $this->markTopParentsUnsynchronized($serviceIp->getServiceDevice());
        }
    }

    /**
     * Called after global QoS settings are changed.
     * Finds out which devices need to be synchronized again and marks them so.
     *
     * @deprecated use a subscriber instead
     */
    public function unsynchronizeSettings(bool $qosEnabled, string $qosDestination, int $qosInterfaceAirOs)
    {
        $oldQosEnabled = $this->options->get(Option::QOS_ENABLED);
        $oldQosDestination = $this->options->get(Option::QOS_DESTINATION);
        $oldQosInterfaceAirOs = $this->options->get(Option::QOS_INTERFACE_AIR_OS);
        $updateDevices = false;
        $updateGateways = false;
        $updateAirOs = false;

        if ($oldQosEnabled !== $qosEnabled) {
            $updateDevices = true;
        }

        if ($oldQosDestination !== $qosDestination) {
            $updateDevices = true;
            $updateGateways = true;
        }

        if ($oldQosInterfaceAirOs !== $qosInterfaceAirOs) {
            $updateAirOs = true;
        }

        if ($updateDevices) {
            $this->markAllDevicesUnsynchronized();
        }

        if ($updateGateways || ($updateDevices && $qosDestination === Option::QOS_DESTINATION_GATEWAY)) {
            $this->markAllGatewaysUnsynchronized();
        }

        if ($updateAirOs) {
            $this->markAllAirOsDevicesUnsynchronized();
        }
    }

    public function markTopParentsUnsynchronized(BaseDevice $device)
    {
        switch ($device->getQosEnabled()) {
            case BaseDevice::QOS_THIS:
                $device->setQosSynchronized(false);
                break;
            case BaseDevice::QOS_ANOTHER:
                foreach ($device->getQosDevices() as $qosDevice) {
                    $this->markTopParentsUnsynchronized($qosDevice);
                }
                break;
        }
    }

    public function markTariffDevicesUnsynchronized(Tariff $tariff): void
    {
        $devices = $serviceDevices = [];

        foreach ($tariff->getServices() as $service) {
            foreach ($service->getServiceDevices() as $serviceDevice) {
                switch ($serviceDevice->getQosEnabled()) {
                    case ServiceDevice::QOS_THIS:
                        $serviceDevices[] = $serviceDevice->getId();
                        break;
                    case ServiceDevice::QOS_ANOTHER:
                        foreach ($serviceDevice->getQosDevices() as $device) {
                            $devices[] = $device->getId();
                        }
                        break;
                }
            }
        }

        if ($devices) {
            $stmt = $this->em->getConnection()->executeQuery(
                '
                    WITH RECURSIVE tree AS (
                        SELECT device_id, qos_enabled
                        FROM device
                        WHERE device_id IN (?)

                        UNION ALL

                        SELECT device_qos.parent_device_id, device.qos_enabled
                        FROM tree
                        INNER JOIN device_qos ON device_qos.device_id = tree.device_id
                        INNER JOIN device ON device.device_id = device_qos.parent_device_id
                        WHERE device.qos_enabled IN (?)
                    )

                    SELECT device_id FROM tree
                    WHERE qos_enabled = ?
                    ORDER BY device_id
                ',
                [
                    $devices,
                    [
                        BaseDevice::QOS_THIS,
                        BaseDevice::QOS_ANOTHER,
                    ],
                    BaseDevice::QOS_THIS,
                ],
                [
                    Connection::PARAM_INT_ARRAY,
                    Connection::PARAM_INT_ARRAY,
                    \PDO::PARAM_INT,
                ]
            );

            $unsynchronizeIds = array_column($stmt->fetchAll(), 'device_id');

            if ($unsynchronizeIds) {
                $qb = $this->em->createQueryBuilder();
                $qb->update(Device::class, 'd')
                    ->set('d.qosSynchronized', ':qosSynchronized')
                    ->where('d.id IN (:ids)')
                    ->setParameter('qosSynchronized', false)
                    ->setParameter('ids', $unsynchronizeIds)
                    ->getQuery()
                    ->execute();
            }
        }

        if ($serviceDevices) {
            $qb = $this->em->createQueryBuilder();
            $qb->update(ServiceDevice::class, 'sd')
                ->set('sd.qosSynchronized', ':qosSynchronized')
                ->where('sd.id IN (:ids)')
                ->setParameter('qosSynchronized', false)
                ->setParameter('ids', $serviceDevices)
                ->getQuery()
                ->execute();
        }
    }

    /**
     * Used when QoS feature is enabled/disabled.
     * Marks all relevant QoS shapers unsynchronized.
     */
    protected function markAllDevicesUnsynchronized()
    {
        $qb = $this->em->createQueryBuilder();
        $qb->update(Device::class, 'd')
            ->set('d.qosSynchronized', ':qosSynchronized')
            ->where('d.qosEnabled = :qosThis')
            ->setParameter('qosSynchronized', false)
            ->setParameter('qosThis', BaseDevice::QOS_THIS)
            ->getQuery()->execute();

        $qb = $this->em->createQueryBuilder();
        $qb->update(ServiceDevice::class, 'sd')
            ->set('sd.qosSynchronized', ':qosSynchronized')
            ->where('sd.qosEnabled = :qosThis')
            ->setParameter('qosSynchronized', false)
            ->setParameter('qosThis', BaseDevice::QOS_THIS)
            ->getQuery()->execute();
    }

    /**
     * Used when QoS destination is changed.
     * Marks all gateway devices unsynchronized.
     */
    public function markAllGatewaysUnsynchronized()
    {
        $qb = $this->em->createQueryBuilder();
        $qb->update(Device::class, 'd')
            ->set('d.qosSynchronized', ':qosSynchronized')
            ->where('d.isGateway = TRUE')
            ->setParameter('qosSynchronized', false)
            ->getQuery()->execute();
    }

    /**
     * Used when QoS interface on AirOS is changed.
     * Marks all AirOS service devices unsynchronized.
     */
    protected function markAllAirOsDevicesUnsynchronized()
    {
        $qb = $this->em->createQueryBuilder();
        $qb->update(ServiceDevice::class, 'sd')
            ->set('sd.qosSynchronized', ':qosSynchronized')
            ->andWhere('sd.qosEnabled = :qosThis')
            ->andWhere('sd.vendor = :vendorAirOs')
            ->setParameter('qosSynchronized', false)
            ->setParameter('qosThis', BaseDevice::QOS_THIS)
            ->setParameter('vendorAirOs', Vendor::AIR_OS)
            ->getQuery()->execute();
    }
}
