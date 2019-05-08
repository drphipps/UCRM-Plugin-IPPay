<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Faker\Factory;
use Faker\Generator;

abstract class BaseFixture extends AbstractFixture
{
    /**
     * @var Generator
     */
    protected $faker;

    public function __construct()
    {
        $this->faker = Factory::create();
    }
}
