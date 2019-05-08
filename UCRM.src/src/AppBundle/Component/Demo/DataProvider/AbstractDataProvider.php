<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Demo\DataProvider;

use AppBundle\Repository\DemoDataRepository;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Faker\Generator;

abstract class AbstractDataProvider
{
    /**
     * @var AbstractPlatform
     */
    protected $databasePlatform;

    /**
     * @var DemoDataRepository
     */
    protected $demoDataRepository;

    /**
     * @var Generator
     */
    protected $faker;

    public function __construct(
        EntityManagerInterface $entityManager,
        DemoDataRepository $demoDataRepository
    ) {
        $this->databasePlatform = $entityManager->getConnection()->getDatabasePlatform();
        $this->demoDataRepository = $demoDataRepository;
        $this->faker = Factory::create();
        $this->faker->seed();
    }

    /**
     * Returns multiplier for random data based on day in week.
     * For example, larger downloads / pings on weekends.
     */
    protected function getDayInWeekBias(\DateTimeInterface $dateTime): float
    {
        switch ((int) $dateTime->format('N')) {
            case 1:
                return 0.8;
            case 2:
                return 1.2;
            case 3:
                return 1.5;
            case 4:
                return 1.0;
            case 5:
                return 0.5;
            case 6:
                return 3.0;
            case 7:
                return 2.5;
            default:
                throw new \InvalidArgumentException();
        }
    }
}
