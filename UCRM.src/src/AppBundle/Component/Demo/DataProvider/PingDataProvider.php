<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Demo\DataProvider;

class PingDataProvider extends AbstractDataProvider
{
    /**
     * @return string[]
     */
    public function getShortTerm(): array
    {
        $serviceDeviceIds = $this->demoDataRepository->getNonLeadServiceDeviceIds();

        $data = [];
        foreach ($serviceDeviceIds as $serviceDeviceId) {
            for ($i = 0; $i < 50; ++$i) {
                $date = new \DateTimeImmutable(sprintf('-%d hours', $i));

                $data[] = sprintf(
                    '(\'%s\', \'%s\', \'%s\', \'%s\')',
                    $serviceDeviceId,
                    $date->format(
                        $this->databasePlatform->getDateTimeTzFormatString()
                    ),
                    $this->faker->randomFloat(
                        2,
                        20 * $this->getDayInWeekBias($date),
                        30 * $this->getDayInWeekBias($date)
                    ),
                    $this->faker->randomFloat(2, 0, 0.1 * $this->getDayInWeekBias($date))
                );
            }
        }

        return [
            'DELETE FROM ping_service_short_term',
            sprintf(
                'INSERT INTO ping_service_short_term (device_id, time, ping, packet_loss) VALUES %s',
                implode(',', $data)
            ),
        ];
    }

    /**
     * @return string[]
     */
    public function getLongTerm(): array
    {
        $serviceDeviceIds = $this->demoDataRepository->getNonLeadServiceDeviceIds();

        $data = [];
        foreach ($serviceDeviceIds as $serviceDeviceId) {
            for ($i = 0; $i < 62; ++$i) {
                $date = new \DateTimeImmutable(sprintf('-%d days', $i));

                $data[] = sprintf(
                    '(\'%s\', \'%s\', \'%s\', \'%s\')',
                    $serviceDeviceId,
                    $date->format('Y-m-d'),
                    $this->faker->randomFloat(
                        2,
                        20 * $this->getDayInWeekBias($date),
                        30 * $this->getDayInWeekBias($date)
                    ),
                    $this->faker->randomFloat(2, 0, 0.1 * $this->getDayInWeekBias($date))
                );
            }
        }

        return [
            'DELETE FROM ping_service_long_term',
            sprintf(
                'INSERT INTO ping_service_long_term (device_id, time, ping, packet_loss) VALUES %s',
                implode(',', $data)
            ),
        ];
    }
}
