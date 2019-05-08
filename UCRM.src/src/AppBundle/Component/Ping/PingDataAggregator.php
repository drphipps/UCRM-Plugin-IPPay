<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Ping;

use AppBundle\Entity\General;
use AppBundle\Entity\Option;
use AppBundle\Facade\OptionsFacade;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManager;

class PingDataAggregator
{
    const TABLE_PING_RAW = 'ping_raw';
    const TABLE_PING_SHORT_TERM = 'ping_short_term';
    const TABLE_PING_LONG_TERM = 'ping_long_term';

    const TABLE_PING_SERVICE_RAW = 'ping_service_raw';
    const TABLE_PING_SERVICE_SHORT_TERM = 'ping_service_short_term';
    const TABLE_PING_SERVICE_LONG_TERM = 'ping_service_long_term';

    const SHORT_TERM_KEEP = '-72 hours';
    const LONG_TERM_KEEP = '-90 days';

    const TYPE_NETWORK = 0;
    const TYPE_SERVICE = 1;

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

    public function setTimezone(string $timezone)
    {
        $this->timezone = $timezone;
    }

    /**
     * Aggregates data from ping_raw to ping_short_term.
     *
     *
     * @throws \Exception
     */
    public function aggregateShortTerm(int $type)
    {
        if ($type === self::TYPE_NETWORK) {
            $rawTable = self::TABLE_PING_RAW;
            $shortTermTable = self::TABLE_PING_SHORT_TERM;
            $innerJoin = 'INNER JOIN device d ON d.device_id = p.device_id';
            $code = General::PING_NETWORK_SHORT_TERM_AGGREGATION_DATE;
        } else {
            $rawTable = self::TABLE_PING_SERVICE_RAW;
            $shortTermTable = self::TABLE_PING_SERVICE_SHORT_TERM;
            $innerJoin = 'INNER JOIN service_device d ON d.service_device_id = p.device_id';
            $code = General::PING_SERVICE_SHORT_TERM_AGGREGATION_DATE;
        }
        $aggregationDate = $this->options->getGeneral($code);

        $this->em->beginTransaction();
        try {
            $connection = $this->em->getConnection();
            $since = (new \DateTime($aggregationDate ?: self::SHORT_TERM_KEEP))->format('Y-m-d H:00:00O');

            // delete inaccurate rows
            $connection->executeUpdate(
                sprintf('DELETE FROM %s WHERE time >= ?', $shortTermTable),
                [
                    $since,
                ]
            );

            // calculate averages from all available raw data
            $connection->executeUpdate(
                sprintf(
                    '
                    INSERT INTO %s (ping_id, device_id, ping, packet_loss, time)
                    SELECT
                        nextval(\'%s_ping_id_seq \'),
                        p.device_id,
                        AVG(p.ping),
                        AVG(p.packet_loss),
                        date_trunc(\'hour\', p.time)
                    FROM %s p
                    %s
                    WHERE p.time >= ?
                    GROUP BY p.device_id, date_trunc(\'hour\', p.time)
                    ',
                    $shortTermTable,
                    $shortTermTable,
                    $rawTable,
                    $innerJoin
                ),
                [
                    $since,
                ]
            );

            // delete no longer needed raw data
            $connection->executeUpdate(
                sprintf('DELETE FROM %s WHERE time < ?', $rawTable),
                [
                    $since,
                ]
            );

            $row = $connection->fetchArray(
                sprintf(
                    '
                        SELECT MAX(time)
                        FROM %s
                    ',
                    $shortTermTable
                )
            );

            if ($row[0]) {
                $since = new \DateTime($since);
                $max = new \DateTime($row[0]);

                if ($max > $since || ! $aggregationDate) {
                    $this->optionsFacade->updateGeneral($code, $max->format('Y-m-d H:00:00O'));
                }
            }

            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    /**
     * Aggregates data from ping_short_term to ping_long_term.
     *
     *
     * @throws \Exception
     */
    public function aggregateLongTerm(int $type)
    {
        $generalRepository = $this->em->getRepository(General::class);
        $tz = new \DateTimeZone($this->getTimezone());

        if ($type === self::TYPE_NETWORK) {
            $shortTermTable = self::TABLE_PING_SHORT_TERM;
            $longTermTable = self::TABLE_PING_LONG_TERM;
            $innerJoin = 'INNER JOIN device d ON d.device_id = p.device_id';
            $aggregationDate = $generalRepository->findOneBy(
                [
                    'code' => General::PING_NETWORK_LONG_TERM_AGGREGATION_DATE,
                ]
            );
        } else {
            $shortTermTable = self::TABLE_PING_SERVICE_SHORT_TERM;
            $longTermTable = self::TABLE_PING_SERVICE_LONG_TERM;
            $innerJoin = 'INNER JOIN service_device d ON d.service_device_id = p.device_id';
            $aggregationDate = $generalRepository->findOneBy(
                [
                    'code' => General::PING_SERVICE_LONG_TERM_AGGREGATION_DATE,
                ]
            );
        }

        $this->em->beginTransaction();
        try {
            $connection = $this->em->getConnection();
            $since = (new \DateTime($aggregationDate->getValue() ?: self::LONG_TERM_KEEP, $tz));
            $sinceTime = $since->format('Y-m-d 00:00:00O');
            $since = $since->format('Y-m-d');
            $keepShortTerm = (new \DateTime(self::SHORT_TERM_KEEP, $tz))->format('Y-m-d 00:00:00O');
            $keepLongTerm = (new \DateTime(self::LONG_TERM_KEEP, $tz))->format('Y-m-d');

            // delete inaccurate rows
            $connection->executeUpdate(
                sprintf('DELETE FROM %s WHERE time >= ?', $longTermTable),
                [
                    $since,
                ]
            );

            // calculate averages from short term data
            $connection->executeUpdate(
                sprintf(
                    '
                    INSERT INTO %s (ping_id, device_id, ping, packet_loss, time)
                    SELECT
                        nextval(\'%s_ping_id_seq \'),
                        p.device_id,
                        AVG(p.ping),
                        AVG(p.packet_loss),
                        date_trunc(\'day\', p.time AT TIME ZONE \'%s\')
                    FROM %s p
                    %s
                    WHERE p.time >= ?
                    GROUP BY p.device_id, date_trunc(\'day\', p.time AT TIME ZONE \'%s\')
                    ',
                    $longTermTable,
                    $longTermTable,
                    $this->getTimezone(),
                    $shortTermTable,
                    $innerJoin,
                    $this->getTimezone()
                ),
                [
                    $sinceTime,
                ]
            );

            // delete no longer needed short term data
            $connection->executeUpdate(
                sprintf('DELETE FROM %s WHERE time < ?', $shortTermTable),
                [
                    $keepShortTerm,
                ]
            );

            // delete no longer needed long term data
            $connection->executeUpdate(
                sprintf('DELETE FROM %s WHERE time < ?', $longTermTable),
                [
                    $keepLongTerm,
                ]
            );

            $row = $connection->fetchArray(
                sprintf(
                    '
                        SELECT MAX(time)
                        FROM %s
                    ',
                    $longTermTable
                )
            );

            if ($row[0]) {
                $since = new \DateTime($since, $tz);
                $max = new \DateTime($row[0], $tz);

                if ($max > $since || ! $aggregationDate->getValue()) {
                    $aggregationDate->setValue($max->format('Y-m-d'));
                    $this->em->flush($aggregationDate);
                }
            }

            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }
}
