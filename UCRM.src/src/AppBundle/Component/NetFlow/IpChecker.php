<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\NetFlow;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Type;

class IpChecker
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var array
     */
    private $serviceIpHashTable = [];

    /**
     * @var array
     */
    private $serviceIpRanges = [];

    /**
     * @var array
     */
    private $interfaceIpHashTable = [];

    /**
     * @var array
     */
    private $interfaceIpRanges = [];

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Reloads IP ranges from database.
     */
    public function __invoke()
    {
        $serviceIpHashTable = $serviceIpRanges = $interfaceIpHashTable = $interfaceIpRanges = [];

        // Get new service IP addresses from database.
        // Use hash tables for non-range IP addresses to optimize.
        /** @var Statement $stmt */
        $stmt = $this->connection->prepare(
            '
            SELECT service.service_id, service_ip.first_ip_address, service_ip.last_ip_address
            FROM service_ip
            INNER JOIN service_device ON service_device.service_device_id = service_ip.service_device_id
            INNER JOIN service ON service.service_id = service_device.service_id
            LEFT JOIN netflow_excluded_ip ON service_ip.ip_address = netflow_excluded_ip.ip_address
            WHERE (service.active_to IS NULL OR service.active_to > ?)
            AND netflow_excluded_ip.id IS NULL 
            ORDER BY service.service_id
            '
        );
        $stmt->bindValue('1', new \DateTime('now', new \DateTimeZone('UTC')), Type::DATETIME);
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            if ($row['first_ip_address'] === $row['last_ip_address']) {
                // If the same IP belongs to more than one service the others are intentionally ignored.
                if (! isset($serviceIpHashTable[$row['first_ip_address']])) {
                    $serviceIpHashTable[$row['first_ip_address']] = $row['service_id'];
                }
            } else {
                $serviceIpRanges[] = $row;
            }
        }

        // Get new interface IP addresses from database.
        // Use hash tables for non-range IP addresses to optimize.
        $stmt = $this->connection->prepare(
            '
                SELECT ip_address, first_ip_address, last_ip_address FROM device_interface_ip
            '
        );
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            if ($row['first_ip_address'] === $row['last_ip_address']) {
                // The primary IP address of the interface is ignored.
                $interfaceIpHashTable[$row['ip_address']] = false;
            } else {
                $interfaceIpRanges[] = $row;
            }
        }

        $this->serviceIpHashTable = $serviceIpHashTable;
        $this->serviceIpRanges = $serviceIpRanges;
        $this->interfaceIpHashTable = $interfaceIpHashTable;
        $this->interfaceIpRanges = $interfaceIpRanges;
    }

    /**
     * Returns Service id for the given IPv4 address.
     *
     *
     * @return int|null
     */
    public function getServiceId(int $ip)
    {
        if (array_key_exists($ip, $this->serviceIpHashTable)) {
            return $this->serviceIpHashTable[$ip];
        }

        // If the IP address is not in the hash table try to find it in ranges and save the result.
        foreach ($this->serviceIpRanges as $ipRange) {
            if ($ip >= $ipRange['first_ip_address'] && $ip <= $ipRange['last_ip_address']) {
                return $this->serviceIpHashTable[$ip] = $ipRange['service_id'];
            }
        }

        return $this->serviceIpHashTable[$ip] = null;
    }

    /**
     * Detects if the given IPv4 address belongs to an interface and is not primary.
     */
    public function isMeasuredInterfaceIp(int $ip): bool
    {
        if (array_key_exists($ip, $this->interfaceIpHashTable)) {
            return $this->interfaceIpHashTable[$ip];
        }

        $isInRange = false;
        $isSecondary = true;
        // If the IP address is not in the hash table try to find it in ranges and save the result.
        foreach ($this->interfaceIpRanges as $ipRange) {
            if ($ip >= $ipRange['first_ip_address'] && $ip <= $ipRange['last_ip_address']) {
                $isInRange = true;
                // The primary IP address of the interface is ignored.
                $isSecondary = $isSecondary && $ip !== $ipRange['ip_address'];
            }
        }

        return $this->interfaceIpHashTable[$ip] = $isInRange && $isSecondary;
    }
}
