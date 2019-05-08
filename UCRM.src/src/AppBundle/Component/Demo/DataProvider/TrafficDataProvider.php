<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Demo\DataProvider;

class TrafficDataProvider extends AbstractDataProvider
{
    /**
     * @return string[]
     */
    public function get(): array
    {
        $serviceIds = $this->demoDataRepository->getNonLeadServiceIds();

        $data = [];
        foreach ($serviceIds as $serviceId) {
            for ($i = 0; $i < 90; ++$i) {
                $date = new \DateTimeImmutable(sprintf('-%d days', $i));
                $max = (100 * 10 ** 9) * $this->getDayInWeekBias($date);
                $download = $this->faker->numberBetween(100 * 10 ** 6, $max);
                $upload = $this->faker->numberBetween(100 * 10 ** 6, $max / 2);
                $data[] = sprintf(
                    '(%d, %d, %d, \'%s\')',
                    $serviceId,
                    $download,
                    $upload,
                    $date->format('Y-m-d')
                );
            }
        }

        return [
            'DELETE FROM service_accounting',
            sprintf(
                'INSERT INTO service_accounting (service_id, download, upload, date) VALUES %s',
                implode(',', $data)
            ),
        ];
    }
}
