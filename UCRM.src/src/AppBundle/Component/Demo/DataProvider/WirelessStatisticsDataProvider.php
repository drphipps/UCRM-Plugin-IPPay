<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Demo\DataProvider;

class WirelessStatisticsDataProvider extends AbstractDataProvider
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
                    '(\'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\')',
                    $serviceDeviceId,
                    $date->format(
                        $this->databasePlatform->getDateTimeTzFormatString()
                    ),
                    $this->faker->numberBetween(85, 100),
                    $this->faker->numberBetween(130, 160),
                    $this->faker->numberBetween(150, 170),
                    $this->faker->numberBetween(-70, -60),
                    $this->faker->numberBetween(-65, -50)
                );
            }
        }

        return [
            'DELETE FROM wireless_statistics_service_short_term',
            sprintf(
                'INSERT INTO wireless_statistics_service_short_term (service_device_id, time, ccq, rx_rate, tx_rate, signal, remote_signal) VALUES %s',
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
                    '(\'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\')',
                    $serviceDeviceId,
                    $date->format('Y-m-d'),
                    $this->faker->numberBetween(85, 100),
                    $this->faker->numberBetween(130, 160),
                    $this->faker->numberBetween(150, 170),
                    $this->faker->numberBetween(-70, -60),
                    $this->faker->numberBetween(-65, -50)
                );
            }
        }

        return [
            'DELETE FROM wireless_statistics_service_long_term',
            sprintf(
                'INSERT INTO wireless_statistics_service_long_term (service_device_id, time, ccq, rx_rate, tx_rate, signal, remote_signal) VALUES %s',
                implode(',', $data)
            ),
        ];
    }
}
