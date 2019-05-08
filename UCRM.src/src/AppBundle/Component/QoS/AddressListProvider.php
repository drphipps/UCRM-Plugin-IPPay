<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\QoS;

use AppBundle\Entity\BaseDevice;
use AppBundle\Entity\Device;
use AppBundle\Entity\Option;
use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceDevice;
use AppBundle\Service\Options;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;

class AddressListProvider
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Options
     */
    private $options;

    public function __construct(EntityManager $em, Options $options)
    {
        $this->em = $em;
        $this->options = $options;
        $this->connection = $em->getConnection();
    }

    public function getList(BaseDevice $device): array
    {
        if (! $this->options->get(Option::QOS_ENABLED)) {
            return [];
        }

        // If global QoS destination is set to gateway and $device is gateway, return QoS list for all services
        if ($this->options->get(Option::QOS_DESTINATION) === Option::QOS_DESTINATION_GATEWAY) {
            if ($device instanceof Device && $device->isGateway()) {
                return $this->getCompleteList();
            }

            return [];
        }

        if ($device->getQosEnabled() !== BaseDevice::QOS_THIS) {
            return [];
        }

        if ($device instanceof ServiceDevice) {
            return $this->getListServiceDevice($device);
        }

        $stmt = $this->connection->executeQuery(
            '
                SELECT sip.ip_address, sip.netmask, sip.first_ip_address, sip.last_ip_address,
                       s.service_id, t.tariff_id, t.download_speed, t.upload_speed
                FROM service_ip sip
                INNER JOIN service_device sd ON sip.service_device_id = sd.service_device_id
                INNER JOIN service s ON s.service_id = sd.service_id
                INNER JOIN tariff t ON t.tariff_id = s.tariff_id
                WHERE sip.service_device_id IN (
                    WITH device_ids AS (
                        WITH RECURSIVE tree AS (
                          SELECT device_id, ARRAY[]::INTEGER[] AS ancestors
                          FROM device
                          WHERE qos_enabled = :qosThis

                          UNION ALL

                          SELECT device_qos.device_id, tree.ancestors || device_qos.parent_device_id
                          FROM device_qos, tree
                          WHERE device_qos.parent_device_id = tree.device_id
                        )
                        
                        SELECT device_id FROM tree
                        WHERE :deviceId = ANY(tree.ancestors) OR tree.device_id = :deviceId
                    )

                    SELECT sd.service_device_id FROM service_device sd
                    INNER JOIN device_interface di ON di.interface_id = sd.interface_id
                    WHERE qos_enabled = :qosAnother AND di.device_id IN (SELECT device_id FROM device_ids)
                    
                    UNION
                    
                    SELECT sdq.service_device_id FROM service_device_qos sdq
                    WHERE sdq.parent_device_id IN (SELECT device_id FROM device_ids)
                )
                AND s.status != :statusEnded
                AND s.deleted_at IS NULL
                ORDER BY sip.ip_address
            ',
            [
                'qosThis' => BaseDevice::QOS_THIS,
                'qosAnother' => BaseDevice::QOS_ANOTHER,
                'deviceId' => $device->getId(),
                'statusEnded' => Service::STATUS_ENDED,
            ]
        );

        return $stmt->fetchAll();
    }

    /**
     * Returns QoS list for all services.
     */
    private function getCompleteList(): array
    {
        $stmt = $this->connection->executeQuery(
            '
                SELECT sip.ip_address, sip.netmask, sip.first_ip_address, sip.last_ip_address,
                       s.service_id, t.tariff_id, t.download_speed, t.upload_speed
                FROM service_ip sip
                INNER JOIN service_device sd ON sip.service_device_id = sd.service_device_id
                INNER JOIN service s ON s.service_id = sd.service_id
                INNER JOIN tariff t ON t.tariff_id = s.tariff_id
                WHERE s.status != :statusEnded
                AND s.deleted_at IS NULL
                ORDER BY sip.ip_address
            ',
            [
                'statusEnded' => Service::STATUS_ENDED,
            ]
        );

        return $stmt->fetchAll();
    }

    /**
     * Returns QoS list for all services connected to ServiceDevice.
     */
    private function getListServiceDevice(ServiceDevice $device): array
    {
        $stmt = $this->connection->executeQuery(
            '
                SELECT sip.ip_address, sip.netmask, sip.first_ip_address, sip.last_ip_address,
                       s.service_id, t.tariff_id, t.download_speed, t.upload_speed
                FROM service_ip sip
                INNER JOIN service_device sd ON sip.service_device_id = sd.service_device_id
                INNER JOIN service s ON s.service_id = sd.service_id
                INNER JOIN tariff t ON t.tariff_id = s.tariff_id
                WHERE sip.service_device_id = :deviceId
                ORDER BY sip.ip_address
            ',
            [
                'deviceId' => $device->getId(),
            ]
        );

        return $stmt->fetchAll();
    }
}
