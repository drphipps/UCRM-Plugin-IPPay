<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Ping;

use AppBundle\Entity\BaseDevice;
use AppBundle\Entity\Device;
use AppBundle\Entity\DeviceOutage;
use AppBundle\Entity\DeviceOutageInterface;
use AppBundle\Entity\ServiceDevice;
use AppBundle\Entity\ServiceDeviceOutage;
use Doctrine\ORM\EntityManager;

class DeviceManager
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @return DevicePing[]|DevicePingCollection
     */
    public function getDeviceQueue(): DevicePingCollection
    {
        $stmt = $this->em->getConnection()->executeQuery(
            '
                SELECT DISTINCT
                    sd.service_device_id,
                    sd.ping_error_count,
                    sd.create_ping_statistics,
                    sd.management_ip_address,
                    first_value(si.ip_address)
                        OVER (PARTITION BY sd.service_device_id ORDER BY si.was_last_connection_successful DESC)
                        AS ip_address
                FROM service_device sd
                LEFT JOIN service_ip si ON si.service_device_id = sd.service_device_id
                INNER JOIN service s ON s.service_id = sd.service_id
                WHERE
                  s.deleted_at IS NULL
                  AND (
                    sd.management_ip_address IS NOT NULL
                    OR ip_address IS NOT NULL
                  )
            '
        );
        $serviceDevices = $stmt->fetchAll();

        $queue = new DevicePingCollection();

        foreach ($serviceDevices as $device) {
            $queue->add(
                new DevicePing(
                    $device['service_device_id'],
                    DevicePing::TYPE_SERVICE,
                    long2ip($device['management_ip_address'] ?? $device['ip_address']),
                    $device['ping_error_count'],
                    $device['create_ping_statistics']
                )
            );
        }

        $stmt = $this->em->getConnection()->executeQuery(
            '
                SELECT DISTINCT
                    d.device_id,
                    d.ping_error_count,
                    d.management_ip_address,
                    dip.ip_address AS search_ip_address,
                    first_value(iip.ip_address)
                        OVER (PARTITION BY d.device_id ORDER BY iip.was_last_connection_successful DESC)
                        AS ip_address
                FROM device d
                LEFT JOIN device_interface i ON i.device_id = d.device_id
                LEFT JOIN device_interface_ip iip ON iip.interface_id = i.interface_id AND iip.is_accessible = TRUE
                LEFT JOIN device_ip dip ON d.search_ip = dip.ip_id 
                WHERE 
                  d.deleted_at IS NULL
                  AND (
                    d.management_ip_address IS NOT NULL
                    OR dip.ip_address IS NOT NULL
                    OR iip.ip_address IS NOT NULL
                  )
            '
        );
        $devices = $stmt->fetchAll();

        foreach ($devices as $device) {
            $queue->add(
                new DevicePing(
                    $device['device_id'],
                    DevicePing::TYPE_NETWORK,
                    long2ip($device['management_ip_address'] ?? $device['ip_address'] ?? $device['search_ip_address']),
                    $device['ping_error_count']
                )
            );
        }

        return $queue;
    }

    public function createOutage(BaseDevice $device): DeviceOutageInterface
    {
        $outage = $this->findCurrentOutage($device);
        if ($outage) {
            return $outage;
        }

        if ($device instanceof ServiceDevice) {
            $outage = new ServiceDeviceOutage();
            $outage->setServiceDevice($device);
        } elseif ($device instanceof Device) {
            $outage = new DeviceOutage();
            $outage->setDevice($device);
        }

        $outage->setOutageStart(new \DateTime());
        $this->em->persist($outage);

        return $outage;
    }

    public function endOutage(BaseDevice $device): void
    {
        $outage = $this->findCurrentOutage($device);
        if (! $outage) {
            return;
        }

        $outage->setOutageEnd(new \DateTime());
    }

    public function findCurrentOutage(BaseDevice $device): ?DeviceOutageInterface
    {
        if ($device instanceof ServiceDevice) {
            $qb = $this->em->getRepository(ServiceDeviceOutage::class)->createQueryBuilder('sdo');
            $qb->where('sdo.serviceDevice = :id')
                ->andWhere('sdo.outageEnd IS NULL')
                ->setParameter('id', $device->getId());
        } else {
            $qb = $this->em->getRepository(DeviceOutage::class)->createQueryBuilder('do');
            $qb->where('do.device = :id')
                ->andWhere('do.outageEnd IS NULL')
                ->setParameter('id', $device->getId());
        }

        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
