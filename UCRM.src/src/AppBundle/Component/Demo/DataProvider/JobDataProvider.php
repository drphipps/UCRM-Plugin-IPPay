<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Demo\DataProvider;

use SchedulingBundle\Entity\Job;

class JobDataProvider extends AbstractDataProvider
{
    public function get(): array
    {
        $jobs = [];
        $jobParams = [];
        $adminIds = $this->demoDataRepository->getAdminIds();
        $adminIds[] = null;

        for ($i = 0; $i < 50; ++$i) {
            $date = $this->faker->dateTimeBetween('-2 days', '+2 days')->format($this->databasePlatform->getDateTimeFormatString());

            $jobs[] = '(?, ?, ?, ?, ?, ?)';
            $adminId = $this->faker->randomElement($adminIds);
            $jobParams[] = $this->getJobTitle();
            $jobParams[] = $adminId ? $date : null;
            $jobParams[] = $this->faker->numberBetween(30, 300);
            $jobParams[] = $adminId;
            $jobParams[] = $adminId ? $this->faker->randomElement(Job::STATUSES_NUMERIC) : Job::STATUS_OPEN;
            $jobParams[] = $this->faker->uuid;
        }

        return [
            'DELETE FROM job',
            [
                'query' => sprintf(
                    'INSERT INTO job (title, date, duration, assigned_user_id, status, uuid) VALUES %s',
                    implode(',', $jobs)
                ),
                'params' => $jobParams,
            ],
        ];
    }

    private function getJobTitle(): string
    {
        return $this->faker->randomElement(
            [
                'New client\'s installation',
                'Rain water leaking through the roof - Site HQ',
                'Main router needs to be replaced',
                'Upgrade Jack\'s service plan',
                'Pick up CPE from Tyson',
                'Buy more UTP cables',
                'UFiber setup at our new location',
                'UNMS installation',
            ]
        );
    }
}
