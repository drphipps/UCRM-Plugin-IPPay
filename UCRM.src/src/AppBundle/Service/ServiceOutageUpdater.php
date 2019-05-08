<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Service;

use AppBundle\Entity\Client;
use Doctrine\DBAL\Connection;

class ServiceOutageUpdater
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function update(): void
    {
        $this->connection->transactional(
            function () {
                $this->connection->executeUpdate('UPDATE service SET has_outage = FALSE');

                $this->connection->executeQuery(
                    '
                        UPDATE service
                        SET
                            has_outage = TRUE
                        FROM service_device_outage sdo
                        JOIN service_device sd ON sdo.service_device_id = sd.service_device_id
                        WHERE
                            sd.service_id = service.service_id
                            AND sdo.outage_end IS NULL
                            AND service.deleted_at IS NULL
                    '
                );

                $this->connection->executeUpdate('UPDATE client SET has_outage = FALSE');

                $this->connection->executeQuery(
                    '
                        UPDATE client
                        SET
                            has_outage = TRUE
                        FROM service s
                        WHERE
                            s.client_id = client.client_id
                            AND s.has_outage = TRUE
                    '
                );
            }
        );
    }

    public function updateClient(Client $client): void
    {
        $this->connection->transactional(
            function () use ($client) {
                $this->connection->executeUpdate(
                    'UPDATE service SET has_outage = FALSE WHERE client_id = :id',
                    [
                        'id' => $client->getId(),
                    ]
                );

                $this->connection->executeQuery(
                    '
                        UPDATE service
                        SET
                            has_outage = TRUE
                        FROM service_device_outage sdo
                        JOIN service_device sd ON sdo.service_device_id = sd.service_device_id
                        WHERE
                            sd.service_id = service.service_id
                            AND sdo.outage_end IS NULL
                            AND service.deleted_at IS NULL
                            AND service.client_id = :id
                    ',
                    [
                        'id' => $client->getId(),
                    ]
                );

                $this->connection->executeUpdate(
                    'UPDATE client SET has_outage = FALSE WHERE client_id = :id',
                    [
                        'id' => $client->getId(),
                    ]
                );

                $this->connection->executeQuery(
                    '
                        UPDATE client
                        SET
                            has_outage = TRUE
                        FROM service s
                        WHERE
                            s.client_id = client.client_id
                            AND s.has_outage = TRUE
                            AND s.client_id = :id
                    ',
                    [
                        'id' => $client->getId(),
                    ]
                );
            }
        );
    }
}
