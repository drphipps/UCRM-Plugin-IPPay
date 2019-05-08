<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

abstract class AbstractTrafficDataProvider
{
    public const PERIOD_TODAY = 'day';
    public const PERIOD_7_DAYS = 'week';
    public const PERIOD_30_DAYS = 'month';

    private const PERIODS = [
        self::PERIOD_TODAY,
        self::PERIOD_7_DAYS,
        self::PERIOD_30_DAYS,
    ];

    /**
     * Note: we need to distinguish between "midnight today" and "just before today's midnight, yesterday"
     * as this will result in different dates, and traffic data only has date (not time). Hence "midnight -1 sec".
     *
     * @return \DateTimeImmutable[]
     *
     * @throws \InvalidArgumentException
     */
    protected function getPeriod(string $period): array
    {
        $now = new \DateTimeImmutable();

        switch ($period) {
            case self::PERIOD_TODAY:
                $fromCurrent = $now->modify('midnight');
                $toCurrent = $now;

                $toPrevious = $fromCurrent->modify('-1 second');
                $fromPrevious = $toPrevious->modify('midnight');

                break;
            case self::PERIOD_7_DAYS:
                $fromCurrent = $now->modify('midnight')->modify('-7 days');
                $toCurrent = $now->modify('midnight')->modify('-1 second');

                $fromPrevious = $fromCurrent->modify('-7 days');
                $toPrevious = $fromCurrent->modify('-1 second');

                break;
            case self::PERIOD_30_DAYS:
                $fromCurrent = $now->modify('midnight')->modify('-30 days');
                $toCurrent = $now->modify('midnight')->modify('-1 second');

                $fromPrevious = $fromCurrent->modify('-30 days');
                $toPrevious = $fromCurrent->modify('-1 second');

                break;
            default:
                throw new \InvalidArgumentException(
                    sprintf(
                        'Unknown period "%s", can be: %s',
                        $period,
                        implode(', ', self::PERIODS)
                    )
                );
        }

        return [$fromCurrent, $toCurrent, $fromPrevious, $toPrevious];
    }
}
