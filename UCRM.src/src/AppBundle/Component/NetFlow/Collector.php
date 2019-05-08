<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\NetFlow;

use Doctrine\DBAL\Connection;

class Collector
{
    // Maximum number of parameters for query is 65535, batch size is limited to include 4 parameters per row.
    private const MAX_BATCH_SIZE = 16000;

    /**
     * @var IpChecker
     */
    private $ipChecker;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var array
     */
    private $serviceData = [];

    /**
     * @var array
     */
    private $interfaceData = [];

    public function __construct(IpChecker $ipChecker, Connection $connection)
    {
        $this->ipChecker = $ipChecker;
        $this->connection = $connection;
    }

    public function collect(\Generator $generator): void
    {
        foreach ($generator as list($source, $target, $bytes)) {
            $this->collectDirection('upload', $source, $bytes);
            $this->collectDirection('download', $target, $bytes);
        }
    }

    public function flush(): void
    {
        while ($this->serviceData) {
            $data = array_splice($this->serviceData, 0, self::MAX_BATCH_SIZE);
            $this->saveToDatabase(
                'INSERT INTO service_accounting_raw (service_id, upload, download, time) VALUES ',
                $data
            );
        }

        while ($this->interfaceData) {
            $data = array_splice($this->interfaceData, 0, self::MAX_BATCH_SIZE);
            $this->saveToDatabase(
                'INSERT INTO ip_accounting_raw (ip, upload, download, time) VALUES ',
                $data
            );
        }
    }

    private function saveToDatabase(string $query, array $data): void
    {
        $parameters = [];
        $values = [];
        foreach ($data as $row) {
            $values[] = '(?, ?, ?, NOW())';
            $parameters[] = $row['id'];
            $parameters[] = $row['upload'];
            $parameters[] = $row['download'];
        }

        $this->connection->executeUpdate($query . implode(',', $values), $parameters);
    }

    private function collectDirection(string $direction, int $ip, int $bytes): void
    {
        if ($serviceId = $this->ipChecker->getServiceId($ip)) {
            if (! array_key_exists($serviceId, $this->serviceData)) {
                $this->serviceData[$serviceId] = [
                    'upload' => 0,
                    'download' => 0,
                    'id' => $serviceId,
                ];
            }

            $this->serviceData[$serviceId][$direction] += $bytes;
        } elseif ($this->ipChecker->isMeasuredInterfaceIp($ip)) {
            if (! array_key_exists($ip, $this->interfaceData)) {
                $this->interfaceData[$ip] = [
                    'upload' => 0,
                    'download' => 0,
                    'id' => $ip,
                ];
            }

            $this->interfaceData[$ip][$direction] += $bytes;
        }
    }
}
