<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\NetFlow;

use AppBundle\Entity\General;
use AppBundle\Entity\Option;
use AppBundle\Facade\OptionsFacade;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManager;

class DataAggregator
{
    public const TYPE_NETWORK = 0;
    public const TYPE_SERVICE = 1;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var string
     */
    private $timezone;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var OptionsFacade
     */
    private $optionsFacade;

    public function __construct(EntityManager $em, Options $options, OptionsFacade $optionsFacade)
    {
        $this->em = $em;
        $this->options = $options;
        $this->optionsFacade = $optionsFacade;
    }

    private function getTimezone(): string
    {
        if (! $this->timezone) {
            $this->timezone = $this->options->get(Option::APP_TIMEZONE, 'UTC');
        }

        return $this->timezone;
    }

    public function setTimezone(string $timezone): void
    {
        $this->timezone = $timezone;
    }

    /**
     * Aggregates data from service_accounting_raw to service_accounting.
     */
    public function aggregate(int $type): void
    {
        $connection = $this->em->getConnection();

        try {
            $connection->beginTransaction();

            $code = $type === self::TYPE_NETWORK
                ? General::NETFLOW_NETWORK_AGGREGATION_DATE
                : General::NETFLOW_SERVICE_AGGREGATION_DATE;

            // Selecting MAX(date) from service_accounting might result in deletion of unrecoverable accounting data
            // in some obscure cases. Therefore this global flag is necessary.
            $aggregationDate = $this->options->getGeneral($code);
            $table = $type === self::TYPE_NETWORK ? 'ip_accounting' : 'service_accounting';

            if ($aggregationDate) {
                $since = new \DateTimeImmutable($aggregationDate, new \DateTimeZone($this->getTimezone()));

                $connection->executeUpdate(
                    sprintf(
                        'DELETE FROM %s WHERE date >= ?',
                        $table
                    ),
                    [
                        $since->format('Y-m-d'),
                    ]
                );

                $connection->executeUpdate(
                    sprintf(
                        'DELETE FROM %s WHERE time < ?',
                        $type === self::TYPE_NETWORK ? 'ip_accounting_raw' : 'service_accounting_raw'
                    ),
                    [
                        $since->format('Y-m-d H:i:sO'),
                    ]
                );
            }

            if ($type === self::TYPE_NETWORK) {
                $query = sprintf(
                    '
                        INSERT INTO ip_accounting (ip, upload, download, date)
                        SELECT
                            ip,
                            SUM(upload),
                            SUM(download),
                            date_trunc(\'day\', time AT TIME ZONE \'%s\')
                        FROM ip_accounting_raw                
                        GROUP BY ip, date_trunc(\'day\', time AT TIME ZONE \'%s\')
                    ',
                    $this->getTimezone(),
                    $this->getTimezone()
                );
            } else {
                // Due to delay in IpChecker there can be some data with id before deferred change.
                $connection->executeUpdate(
                    '
                        UPDATE service_accounting_raw a
                        SET service_id = (SELECT s.superseded_by_service_id FROM service s WHERE s.service_id = a.service_id)
                        WHERE a.service_id IN (SELECT service_id FROM service WHERE superseded_by_service_id IS NOT NULL AND deleted_at IS NOT NULL) 
                    '
                );

                $query = sprintf(
                    '
                        INSERT INTO service_accounting (service_id, upload, download, date)
                        SELECT
                            service.service_id,
                            SUM(upload),
                            SUM(download),
                            date_trunc(\'day\', time AT TIME ZONE \'%s\')
                        FROM service_accounting_raw
                        INNER JOIN service
                            ON service.service_id = service_accounting_raw.service_id                
                        GROUP BY service.service_id, date_trunc(\'day\', time AT TIME ZONE \'%s\')
                    ',
                    $this->getTimezone(),
                    $this->getTimezone()
                );
            }

            $connection->executeUpdate($query);

            $row = $connection->fetchArray(
                sprintf(
                    '
                        SELECT MAX(date)
                        FROM %s
                    ',
                    $table
                )
            );

            if ($row[0]) {
                $max = new \DateTimeImmutable($row[0], new \DateTimeZone($this->getTimezone()));

                if (! isset($since) || $max > $since) {
                    $this->optionsFacade->updateGeneral($code, $max->format('Y-m-d'));
                }
            }

            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }
}
