<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Service;
use Doctrine\DBAL\Connection;

class ClientStatusUpdater
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
                $this->updateDirectly();
            }
        );
    }

    /**
     * Do the needful without starting a transaction:
     * in some contexts, we already are in a transaction
     * and nesting transactions risks deadlocks.
     */
    public function updateDirectly(): void
    {
        $this->resetStatuses();
        $this->updateSuspendedServiceStatus();
        $this->updateOverdueInvoiceStatus();
    }

    private function resetStatuses(): void
    {
        $this->connection->executeUpdate(
            '
                UPDATE client
                SET
                    has_suspended_service = FALSE,
                    has_overdue_invoice = FALSE
                WHERE
                    deleted_at IS NULL
            '
        );
    }

    private function updateSuspendedServiceStatus(): void
    {
        $this->connection->executeUpdate(
            '
                UPDATE client
                SET
                    has_suspended_service = TRUE
                FROM service s
                WHERE
                    s.client_id = client.client_id
                    AND s.status = ?
                    AND s.deleted_at IS NULL
                    AND client.deleted_at IS NULL
            ',
            [
                Service::STATUS_SUSPENDED,
            ]
        );
    }

    private function updateOverdueInvoiceStatus(): void
    {
        $overdueDate = new \DateTime('today midnight');
        $overdueDate->setTimezone(new \DateTimeZone('UTC'));

        $this->connection->executeUpdate(
            '
                UPDATE client
                SET
                    has_overdue_invoice = TRUE
                FROM invoice i
                WHERE
                    i.client_id = client.client_id
                    AND i.invoice_status IN (?)
                    AND i.due_date <= ?
                    AND client.deleted_at IS NULL
            ',
            [
                Invoice::UNPAID_STATUSES,
                $overdueDate->format(\DateTime::ISO8601),
            ],
            [
                Connection::PARAM_INT_ARRAY,
                \PDO::PARAM_STR,
            ]
        );
    }
}
