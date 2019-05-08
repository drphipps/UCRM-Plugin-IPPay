<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Service;

use AppBundle\Util\DateTimeImmutableFactory;

class TimePeriod
{
    /**
     * @var \DateTimeImmutable|null
     */
    public $startDate;

    /**
     * @var \DateTimeImmutable|null
     */
    public $endDate;

    public static function createYear(int $year): TimePeriod
    {
        $startDate = DateTimeImmutableFactory::createDateFromNumbers($year, 1, 1);
        $endDate = $startDate->modify(
            'first day of january next year midnight -1 second'
        );

        return self::create($startDate, $endDate);
    }

    public static function allTime(): TimePeriod
    {
        return self::create(null, null);
    }

    public static function createCurrentMonth(): TimePeriod
    {
        return self::createFromString(
            'first day of this month midnight',
            'first day of next month midnight'
        );
    }

    public static function createFromString(
        ?string $startDateString = null,
        ?string $endDateString = null
    ): TimePeriod {
        $startDate = $startDateString ? DateTimeImmutableFactory::createWithoutFormat($startDateString) : null;
        $endDate = $endDateString ? DateTimeImmutableFactory::createWithoutFormat($endDateString) : null;

        return self::create($startDate, $endDate);
    }

    public static function create(
        ?\DateTimeInterface $startDate,
        ?\DateTimeInterface $endDate
    ): TimePeriod {
        $timePeriodData = new self();
        $timePeriodData->startDate = $startDate ? DateTimeImmutableFactory::createFromInterface($startDate) : null;
        $timePeriodData->endDate = $endDate ? DateTimeImmutableFactory::createFromInterface($endDate) : null;

        return $timePeriodData;
    }
}
