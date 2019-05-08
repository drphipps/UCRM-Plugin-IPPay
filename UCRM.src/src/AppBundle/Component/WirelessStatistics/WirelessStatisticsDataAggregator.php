<?php
/*
* @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
* @see https://www.ubnt.com/
*/

declare(strict_types=1);

namespace AppBundle\Component\WirelessStatistics;

use AppBundle\Entity\General;
use AppBundle\Entity\Option;
use AppBundle\Facade\OptionsFacade;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManager;

class WirelessStatisticsDataAggregator
{
    const TABLE_WIRELESS_STATISTICS_SHORT_TERM = 'wireless_statistics_short_term';
    const TABLE_WIRELESS_STATISTICS_LONG_TERM = 'wireless_statistics_long_term';

    const TABLE_WIRELESS_STATISTICS_SERVICE_SHORT_TERM = 'wireless_statistics_service_short_term';
    const TABLE_WIRELESS_STATISTICS_SERVICE_LONG_TERM = 'wireless_statistics_service_long_term';

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
     * @throws \Throwable
     */
    public function aggregateLongTerm(int $type)
    {
        $tz = new \DateTimeZone($this->getTimezone());

        if ($type === self::TYPE_NETWORK) {
            $shortTermTable = self::TABLE_WIRELESS_STATISTICS_SHORT_TERM;
            $longTermTable = self::TABLE_WIRELESS_STATISTICS_LONG_TERM;
            $code = General::WIRELESS_STATISTICS_AGGREGATION_DATE;
            $deviceColumnName = 'device_id';
        } elseif ($type === self::TYPE_SERVICE) {
            $shortTermTable = self::TABLE_WIRELESS_STATISTICS_SERVICE_SHORT_TERM;
            $longTermTable = self::TABLE_WIRELESS_STATISTICS_SERVICE_LONG_TERM;
            $code = General::WIRELESS_STATISTICS_SERVICE_AGGREGATION_DATE;
            $deviceColumnName = 'service_device_id';
        } else {
            return;
        }

        $aggregationDate = $this->options->getGeneral($code);

        $this->em->beginTransaction();
        try {
            $connection = $this->em->getConnection();
            $since = (new \DateTime($aggregationDate ?: self::LONG_TERM_KEEP, $tz));
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
                    INSERT INTO %s (id, %s, ccq, rx_rate, tx_rate, signal, remote_signal, time)
                    SELECT
                        nextval(\'%s_id_seq \'),
                        p.%s,
                        AVG(p.ccq),
                        AVG(p.rx_rate),
                        AVG(p.tx_rate),
                        AVG(p.signal),
                        AVG(p.remote_signal),
                        date_trunc(\'day\', p.time AT TIME ZONE \'%s\')
                    FROM %s p
                    WHERE p.time >= ?
                    GROUP BY p.%s, date_trunc(\'day\', p.time AT TIME ZONE \'%s\')
                    ',
                    $longTermTable,
                    $deviceColumnName,
                    $longTermTable,
                    $deviceColumnName,
                    $this->getTimezone(),
                    $shortTermTable,
                    $deviceColumnName,
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

                if ($max > $since || ! $aggregationDate) {
                    $this->optionsFacade->updateGeneral($code, $max->format('Y-m-d'));
                }
            }

            $this->em->commit();
        } catch (\Throwable $e) {
            $this->em->rollback();

            throw $e;
        }
    }
}
