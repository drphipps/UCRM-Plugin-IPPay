<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Util;

use AppBundle\Entity\Client;
use AppBundle\Entity\Service;
use AppBundle\Entity\SuspensionPeriod;

class Invoicing
{
    public const PRORATED_NO = 0;
    public const PRORATED_START = 1;
    public const PRORATED_END = 2;
    public const PRORATED_BOTH = 3;

    public const PERIOD_DIRECTION_START = 0;
    public const PERIOD_DIRECTION_END = 1;

    public const BILLING_CYCLE_REAL = 0;
    public const BILLING_CYCLE_30DAY = 1;
    public const BILLING_CYCLES = [
        self::BILLING_CYCLE_REAL => 'Real days count',
        self::BILLING_CYCLE_30DAY => '30 days in month',
    ];

    public const MAX_PERIOD_LIMIT = '+3 years +1 month';

    /**
     * Calculates period to be invoiced.
     * Start of period is invoicingStart from service or last invoiced period day + 1 when some invoice exists.
     * End of period is start + X months (periodStartDay - 1) or closest (periodStartDay - 1) if pro-rated from start.
     * If $proratedSeparately is false, calculated period is the pro-rated part + 1 whole period after that.
     *
     * NOTE: Max possible periodStartDay is 28, if bigger last day of month is used.
     * NOTE 2: Period end can be limited with $invoicingStop (e.g. service end date).
     */
    public static function getInvoicedPeriod(
        \DateTimeInterface $invoicedFrom,
        int $periodStartDay,
        int $periodMonths,
        bool $proratedSeparately,
        ?\DateTimeInterface $invoicingStop = null
    ): array {
        $invoicedFrom = self::getDateUTC($invoicedFrom);
        if ($invoicingStop !== null) {
            $invoicingStop = self::getDateUTC($invoicingStop);
        }

        $period = self::getWholePeriod($invoicedFrom, $periodStartDay, $periodMonths);
        if ($period['invoicedFrom']->format('Y-m-d') !== $invoicedFrom->format('Y-m-d')) {
            // invoicing start is pro-rated
            $period['invoicedFrom'] = $invoicedFrom;

            if (! $proratedSeparately) {
                $period['invoicedTo'] = $period['invoicedTo']->modify('+1 day');
                $period['invoicedTo'] = self::getPeriodEnd($period['invoicedTo'], $periodStartDay, $periodMonths);
            }
        }

        if ($invoicingStop && $invoicingStop < $period['invoicedTo']) {
            $period['invoicedTo'] = $invoicingStop;
        }

        if ($period['invoicedFrom'] > $period['invoicedTo']) {
            $period['invoicedFrom'] = $period['invoicedTo'] = null;
        }

        return $period;
    }

    /**
     * Same as getInvoicedPeriod, but takes Service as param.
     *
     *
     * @return \DateTime[]
     */
    public static function getInvoicedPeriodService(
        Service $service,
        ?\DateTimeInterface $fromDate = null,
        bool $respectInvoicingStop = true
    ): array {
        if ($fromDate) {
            $invoicedFrom = self::getDateUTC($fromDate);
        } else {
            $invoicedFrom = self::getFirstDayForInvoicing($service);
        }

        $invoicingStop = $respectInvoicingStop ? $service->getActiveTo() : null;

        return self::getInvoicedPeriod(
            $invoicedFrom,
            $service->getInvoicingPeriodStartDay(),
            $service->getTariffPeriodMonths(),
            $service->isInvoicingProratedSeparately(),
            $invoicingStop
        );
    }

    private static function getFirstDayForInvoicing(Service $service): \DateTime
    {
        if (
            $service->getInvoicingLastPeriodEnd()
            && $service->getInvoicingLastPeriodEnd() >= $service->getInvoicingStart()
        ) {
            return (clone $service->getInvoicingLastPeriodEnd())->modify('+1 day');
        }

        return clone $service->getInvoicingStart();
    }

    /**
     * @return array|\DateTime[]|null[]
     */
    public static function getMaxInvoicedPeriodService(
        Service $service,
        \DateTimeInterface $invoicingDate,
        \DateTimeInterface $fromDate = null
    ): array {
        $limitInvoicedTo = self::calculateLimitInvoicedToAdjustments($service, self::getDateUTC($invoicingDate));

        $period = self::getInvoicedPeriodService($service, $fromDate);

        if (null === $period['invoicedTo'] || self::isPeriodFirstAndProrated($service, $period)) {
            return $period;
        }

        while (true) {
            $fromDate = (clone $period['invoicedTo'])->modify('+1 day');
            $nextPeriod = self::getInvoicedPeriodService($service, $fromDate);

            if (! $nextPeriod['invoicedTo'] || $nextPeriod['invoicedTo'] > $limitInvoicedTo) {
                break;
            }

            $period['invoicedTo'] = $nextPeriod['invoicedTo'];
        }

        return $period;
    }

    public static function getPeriodsForInvoicing(
        Service $service,
        \DateTimeInterface $invoicingDate,
        bool $stopInvoicingWhenSuspended
    ): \Generator {
        $limitInvoicedTo = self::calculateLimitInvoicedToAdjustments($service, self::getDateUTC($invoicingDate));
        $suspensionPeriods = $stopInvoicingWhenSuspended ? self::getSuspensionPeriods($service) : [];

        $fromDate = self::getFirstDayForInvoicing($service);
        $period = null;
        do {
            $nextPeriod = self::getInvoicedPeriod(
                $fromDate,
                $service->getInvoicingPeriodStartDay(),
                $service->getTariffPeriodMonths(),
                true,
                $service->getActiveTo()
            );

            // In case of first ever invoiced period, when prorated invoices should NOT be generated separately,
            // and we're invoicing forwards, we need to move the invoicedTo limit to accommodate the prorating.
            // (in case of backwards, the limit is always fine as it's back from today)
            if (
                Service::INVOICING_FORWARDS === $service->getInvoicingPeriodType()
                && $period === null
                && $nextPeriod['invoicedTo']
                && ! $service->isInvoicingProratedSeparately()
                && ! $service->getInvoicingLastPeriodEnd()
                && self::PRORATED_NO !== self::isProrated(
                    $nextPeriod['invoicedFrom'],
                    $nextPeriod['invoicedTo'],
                    $service->getTariffPeriodMonths(),
                    $service->getInvoicingPeriodStartDay()
                )
            ) {
                $limitInvoicedTo = $limitInvoicedTo->modify(
                    sprintf(
                        '+%d days',
                        self::getDaysBetween($nextPeriod['invoicedFrom'], $nextPeriod['invoicedTo'])
                    )
                );
            }

            if (! $nextPeriod['invoicedTo'] || $nextPeriod['invoicedTo'] > $limitInvoicedTo) {
                if ($period) {
                    yield $period;
                }
                break;
            }

            if (! $stopInvoicingWhenSuspended || ! self::isWholePeriodSuspended($suspensionPeriods, $nextPeriod)) {
                if (self::isPeriodFirstAndProrated($service, $nextPeriod)) {
                    yield $nextPeriod;
                    break;
                }

                if ($period) {
                    $period['invoicedTo'] = $nextPeriod['invoicedTo'];
                } else {
                    $period = $nextPeriod;
                }
            } elseif ($period) {
                yield $period;
                $period = null;
            }

            $fromDate = (clone $nextPeriod['invoicedTo'])->modify('+1 day');
        } while (true);
    }

    private static function calculateLimitInvoicedToAdjustments(Service $service, \DateTime $limitInvoicedTo): \DateTime
    {
        /*
            NextInvoicingDayAdjustment has to be handled first, and possible forwards month modification afterwards.
            This is because if NextInvoicingDayAdjustment would take us to the first day of next month and the
            forwards modification would be after, it would not fit in months with different day count.

            For example:
                Invoicing date: 2018-02-12
                NextInvoicingDayAdjustment: 17
                TariffPeriodMonths: 1 (forwards)

            Correct:
                limitInvoicedTo = 2018-02-12 + 17 days = 2018-03-01
                limitInvoicedTo = 2018-03-01 + 1 month = 2018-04-01

            Wrong:
                limitInvoicedTo = 2018-02-12 + 1 month = 2017-03-12
                limitInvoicedTo = 2017-03-12 + 17 days = 2018-03-29
        */
        $limitInvoicedTo = $limitInvoicedTo->modify(sprintf('+%d days', $service->getNextInvoicingDayAdjustment()));

        /*
            In case of forwards invoicing we must move the "invoice to" limit forward by X months.

            \DateTime::modify('+X months') cannot be used, because it's not precise,
            we need to move forward by day count of next month.

            Wrong:
                2019-01-29 + 1 month = 2019-03-01

            Correct:
                2019-01-29 + 28 days = 2019-02-26
         */
        if (Service::INVOICING_FORWARDS === $service->getInvoicingPeriodType()) {
            $periodMonths = $service->getTariffPeriodMonths();
            do {
                $nextMonth = (clone $limitInvoicedTo)->modify('first day of next month');
                $limitInvoicedTo = $limitInvoicedTo->modify(sprintf('+%d days', $nextMonth->format('t')));
                --$periodMonths;
            } while ($periodMonths > 0);
        }

        return $limitInvoicedTo;
    }

    public static function isLikelyToHaveFutureInvoice(Client $client, bool $stopInvoicingWhenSuspended): bool
    {
        foreach ($client->getNotDeletedServices() as $service) {
            if (in_array($service->getStatus(), [Service::STATUS_PREPARED, Service::STATUS_PREPARED_BLOCKED], true)) {
                return true;
            }

            if ($service->getActiveTo() === null) {
                return true;
            }

            $periods = self::getPeriodsForInvoicing(
                $service,
                DateTimeImmutableFactory::createFromInterface($service->getActiveTo()),
                $stopInvoicingWhenSuspended
            );

            if (iterator_to_array($periods, false)) {
                return true;
            }
        }

        return false;
    }

    private static function isWholePeriodSuspended(array $suspensionPeriods, array $period): bool
    {
        $date = $period['invoicedFrom'];

        /** @var SuspensionPeriod $suspensionPeriod */
        foreach ($suspensionPeriods as $suspensionPeriod) {
            if ($date < $suspensionPeriod->getSince()) {
                break;
            }

            if (! $suspensionPeriod->getUntil()) {
                return true;
            }
            if ($date <= $suspensionPeriod->getUntil()) {
                $date = (clone $suspensionPeriod->getUntil())->modify('+1 day');
            }
        }

        return $date > $period['invoicedTo'];
    }

    private static function getSuspensionPeriods(Service $service): array
    {
        while ($service->getStatus() === Service::STATUS_OBSOLETE && $service->getSupersededByService()) {
            $service = $service->getSupersededByService();
        }

        return $service->getSuspensionPeriods()->toArray();
    }

    /**
     * Calculates whole invoicing period. That is, period that is not pro-rated in any way.
     * Invoiced quantity is than calculated as day count of invoiced period divided by day count of whole period.
     *
     * NOTE: Period direction determines whether to create the period by moving invoicedTo date to closest possible
     *       period end, or create the period by moving invoicedFrom to closest possible period start.
     *
     * @return \DateTime[]
     */
    public static function getWholePeriod(
        \DateTimeInterface $invoicedFrom,
        int $periodStartDay,
        int $periodMonths,
        int $direction = self::PERIOD_DIRECTION_END
    ): array {
        // make the original DateTime objects safe
        $invoicedFrom = self::getDateUTC($invoicedFrom);
        $invoicedTo = clone $invoicedFrom;

        $fromDay = (int) $invoicedFrom->format('j');
        $periodStartDay = $periodStartDay > 28 ? 31 : $periodStartDay;
        $proRated = ! self::isLastDayOfPeriod($periodStartDay, $fromDay, (int) $invoicedFrom->format('t'));

        if ($direction === self::PERIOD_DIRECTION_END) {
            if ($proRated) {
                // pro-rated
                while (true) {
                    $currentDay = (int) $invoicedTo->format('j');
                    $lastDayOfMonth = (int) $invoicedTo->format('t');
                    if (
                        ($periodStartDay === 1 && $currentDay === $lastDayOfMonth) ||
                        ($periodStartDay === 31 && $currentDay === $lastDayOfMonth - 1) ||
                        $currentDay === $periodStartDay - 1
                    ) {
                        break;
                    }
                    $invoicedTo = $invoicedTo->modify('+1 day');
                }

                $invoicedFrom = self::getPeriodStart($invoicedFrom, $periodStartDay, $periodMonths);
            } else {
                $invoicedTo = self::getPeriodEnd($invoicedTo, $periodStartDay, $periodMonths);
            }
        } else {
            if ($proRated) {
                // pro-rated
                while (true) {
                    $currentDay = (int) $invoicedFrom->format('j');
                    $lastDayOfMonth = (int) $invoicedFrom->format('t');
                    if (
                        ($periodStartDay === 31 && $currentDay === $lastDayOfMonth) ||
                        $currentDay === $periodStartDay
                    ) {
                        break;
                    }
                    $invoicedFrom = $invoicedFrom->modify('-1 day');
                }
            }

            $invoicedTo = clone $invoicedFrom;
            $invoicedTo = self::getPeriodEnd($invoicedTo, $periodStartDay, $periodMonths);
        }

        return [
            'invoicedFrom' => $invoicedFrom,
            'invoicedTo' => $invoicedTo,
        ];
    }

    /**
     * @return \DateTime[][]
     */
    public static function getInvoicedPeriods(Service $service, ?\DateTimeInterface $fromDate = null): array
    {
        if ($fromDate) {
            $invoicedFrom = self::getDateUTC($fromDate);
        } elseif ($service->getInvoicingLastPeriodEnd()) {
            $invoicedFrom = (clone $service->getInvoicingLastPeriodEnd())->modify('+1 day');
        } else {
            $invoicedFrom = $service->getInvoicingStart();
        }

        if (! $invoicedFrom) {
            return [];
        }

        $invoicingStop = $service->getActiveTo();

        return self::getPeriods($service, $invoicedFrom, $invoicingStop);
    }

    /**
     * @return \DateTime[][]
     */
    public static function getPeriodsForDataUsage(Service $service, \DateTimeInterface $fromDate): array
    {
        $serviceActiveTo = $service->getActiveTo() ?: new \DateTimeImmutable();

        $invoicingStop = $serviceActiveTo->format('Y-m-d') < $fromDate->format('Y-m-d')
            ? $fromDate
            : $serviceActiveTo;

        return self::getPeriods($service, $fromDate, $invoicingStop);
    }

    /**
     * @return string[][]
     */
    public static function getInvoicedPeriodsForm(
        Service $service,
        \DateTimeInterface $fromDate = null,
        Formatter $formatter = null
    ): array {
        $periods = self::getInvoicedPeriods($service, $fromDate);
        $from = $to = [];

        foreach ($periods as $period) {
            if (! $period['invoicedFrom']) {
                break;
            }
            $ymdFrom = $period['invoicedFrom']->format('Y-m-d');
            $ymdTo = $period['invoicedTo']->format('Y-m-d');

            $from[$ymdFrom] = $formatter
                ? $formatter->formatDate($period['invoicedFrom'], Formatter::DEFAULT, Formatter::NONE)
                : $ymdFrom;

            $to[$ymdTo] = $formatter
                ? $formatter->formatDate($period['invoicedTo'], Formatter::DEFAULT, Formatter::NONE)
                : $ymdTo;
        }

        return [$from, $to];
    }

    /**
     * @return string[][]
     */
    public static function getServiceInvoiceablePeriods(Service $service, ?Formatter $formatter): array
    {
        $lastPeriodEnd = clone ($service->getInvoicingLastPeriodEnd() ?: $service->getInvoicingStart());
        $since = max(
            $service->getInvoicingStart(),
            $lastPeriodEnd->modify(sprintf('-%d months', self::getPastMonthsWindow($service)))
        );

        return self::getInvoicedPeriodsForm(
            $service,
            self::getDateUTC($since),
            $formatter
        );
    }

    /**
     * Calculates next invoicing day (date when draft will be created) from invoiced period.
     * Default for backward invoicing is 1 day after invoiced period.
     * Default for forward invoicing is 1 day before invoiced period.
     */
    public static function getNextInvoicingDay(Service $service, array $period = null): \DateTime
    {
        if (! $period) {
            $period = self::getInvoicedPeriodService($service);
            if (! $period['invoicedFrom'] || ! $period['invoicedTo']) {
                $period = self::getInvoicedPeriodService($service, null, false);
            }
        }
        $adjustment = $service->getNextInvoicingDayAdjustment() * -1;

        if ($service->getInvoicingPeriodType() === Service::INVOICING_BACKWARDS) {
            $nextInvoicingDay = (clone $period['invoicedTo'])->modify(sprintf('%s days', 1 + $adjustment));
        } else {
            $nextInvoicingDay = (clone $period['invoicedFrom'])->modify(sprintf('%s days', $adjustment));
        }

        return $nextInvoicingDay;
    }

    /**
     * Calculates quantity between 2 dates given period length and start day.
     *
     * NOTE: Max possible periodStartDay is 28, if bigger last day of month is used.
     */
    public static function getPeriodQuantity(
        \DateTimeInterface $invoicedFrom,
        \DateTimeInterface $invoicedTo,
        int $periodMonths,
        int $periodStartDay,
        int $billingCycleType = self::BILLING_CYCLE_30DAY,
        float $quantity = 0.0
    ): float {
        $invoicedFrom = self::getDateUTC($invoicedFrom);
        $invoicedTo = self::getDateUTC($invoicedTo);

        if ($invoicedFrom > $invoicedTo) {
            throw new \InvalidArgumentException('invoicedFrom cannot be bigger than invoicedTo');
        }

        if ($invoicedFrom === $invoicedTo) {
            $wholePeriod = self::getWholePeriod($invoicedFrom, $periodStartDay, $periodMonths);
            $daysOfPeriod = 30 * $periodMonths;
            if ($billingCycleType === self::BILLING_CYCLE_REAL) {
                $daysOfPeriod = self::getDaysBetween($wholePeriod['invoicedFrom'], $wholePeriod['invoicedTo']);
            }

            return $quantity + (1 / $daysOfPeriod);
        }

        $period = self::getInvoicedPeriod($invoicedFrom, $periodStartDay, $periodMonths, true);
        if ($invoicedTo > $period['invoicedTo']) {
            $nextInvoicedTo = $invoicedTo;
            $invoicedTo = clone $period['invoicedTo'];

            $nextInvoicedFrom = (clone $invoicedTo)->modify('+1 day');
        }

        $isProrated = self::isProrated($invoicedFrom, $invoicedTo, $periodMonths, $periodStartDay);
        if ($isProrated === self::PRORATED_NO) {
            while (true) {
                $invoicedFrom = $invoicedFrom->modify('+1 day');

                if ($invoicedFrom->format('Y-m-d') === $invoicedTo->format('Y-m-d')) {
                    $quantity += 1.0;

                    $lastInvoicedTo = (clone $invoicedFrom)->modify('+1 day');
                    $period = self::getInvoicedPeriod($lastInvoicedTo, $periodStartDay, $periodMonths, true);
                }

                if ($period['invoicedFrom'] >= $invoicedTo) {
                    break;
                }
            }
        } else {
            $invoicedDays = self::getDaysBetween($invoicedFrom, $invoicedTo);
            $wholePeriod = self::getWholePeriod(
                $invoicedFrom,
                $periodStartDay,
                $periodMonths,
                $isProrated === self::PRORATED_END ? self::PERIOD_DIRECTION_START : self::PERIOD_DIRECTION_END
            );
            $daysOfPeriod = 30 * $periodMonths;
            if ($billingCycleType === self::BILLING_CYCLE_REAL) {
                $daysOfPeriod = self::getDaysBetween($wholePeriod['invoicedFrom'], $wholePeriod['invoicedTo']);
            }

            $quantity += ($invoicedDays / $daysOfPeriod);
        }

        if (isset($nextInvoicedFrom, $nextInvoicedTo)) {
            return self::getPeriodQuantity(
                $nextInvoicedFrom,
                $nextInvoicedTo,
                $periodMonths,
                $periodStartDay,
                $billingCycleType,
                $quantity
            );
        }

        return $quantity;
    }

    /**
     * Determines if SINGLE invoiced period is pro-rated.
     */
    public static function isProrated(
        \DateTimeInterface $invoicedFrom,
        \DateTimeInterface $invoicedTo,
        int $periodMonths,
        int $periodStartDay
    ): int {
        $invoicedFrom = self::getDateUTC($invoicedFrom);
        $invoicedTo = self::getDateUTC($invoicedTo);
        $period = self::getInvoicedPeriod($invoicedFrom, $periodStartDay, $periodMonths, true);

        if ($invoicedTo > $period['invoicedTo']) {
            throw new \InvalidArgumentException('Argument is not single invoiced period.');
        }

        $fromDay = (int) $invoicedFrom->format('j');
        $periodStartDay = $periodStartDay > 28 ? 31 : $periodStartDay;

        $toDay = $invoicedTo->format('j');
        $periodEndDay = $period['invoicedTo']->format('j');
        $proRated = ! self::isLastDayOfPeriod($periodStartDay, $fromDay, (int) $invoicedFrom->format('t'));

        if (! $proRated && $invoicedTo->format('Y-m-d') === $period['invoicedTo']->format('Y-m-d')) {
            return self::PRORATED_NO;
        }
        if (! $proRated && $toDay !== $periodEndDay) {
            return self::PRORATED_END;
        }
        if ($proRated && $toDay === $periodEndDay) {
            return self::PRORATED_START;
        }

        return self::PRORATED_BOTH;
    }

    public static function getDateUTC(\DateTimeInterface $date): \DateTime
    {
        return DateTimeFactory::createFromFormat(
            'Y-m-d H:i:s',
            $date->format('Y-m-d 00:00:00'),
            new \DateTimeZone('UTC')
        );
    }

    public static function getNextFinancialNumber(
        int $length,
        string $prefix = null,
        int $initNumber = null,
        int $lastNumber = null
    ): string {
        $nbr = $prefix ? self::printFinancialPrefix($prefix) : '';
        $nextNumber = max(1, $initNumber, $lastNumber + 1);
        $nbr .= str_pad((string) $nextNumber, $length, '0', STR_PAD_LEFT);

        return $nbr;
    }

    public static function printFinancialPrefix(string $prefix): string
    {
        return str_replace(
            [
                'YYYY',
                'YY',
                'MM',
            ],
            [
                date('Y'),
                date('y'),
                date('m'),
            ],
            $prefix
        );
    }

    /**
     * @return \DateTime[][]
     */
    private static function getPeriods(
        Service $service,
        \DateTimeInterface $invoicedFrom,
        ?\DateTimeInterface $invoicingStop
    ): array {
        $periods = [];
        $maxInvoicedTo = DateTimeFactory::createFromInterface($invoicedFrom)->modify(self::MAX_PERIOD_LIMIT);
        $periodStartDay = $service->getInvoicingPeriodStartDay();
        $periodMonths = $service->getTariffPeriodMonths();
        $proratedSeparately = $service->isInvoicingProratedSeparately();

        do {
            $period = self::getInvoicedPeriod(
                $invoicedFrom,
                $periodStartDay,
                $periodMonths,
                $proratedSeparately,
                $invoicingStop
            );

            $periods[] = $period;
            if (null === $period['invoicedTo']) {
                break;
            }

            $invoicedFrom = (clone $period['invoicedTo'])->modify('+1 day');
        } while ($period['invoicedTo'] < $maxInvoicedTo);

        return $periods;
    }

    private static function isPeriodFirstAndProrated(Service $service, array $period): bool
    {
        return
            null === $service->getInvoicingLastPeriodEnd() &&
            $service->isInvoicingProratedSeparately() &&
            self::PRORATED_NO !== self::isProrated(
                $period['invoicedFrom'],
                $period['invoicedTo'],
                $service->getTariffPeriodMonths(),
                $service->getInvoicingPeriodStartDay()
            );
    }

    /**
     * Returns count of days between 2 dates (inclusive).
     */
    private static function getDaysBetween(\DateTime $start, \DateTime $end): int
    {
        $start = self::getDateUTC($start);
        $end = self::getDateUTC($end);

        $days = 0;
        while ($start <= $end) {
            ++$days;
            $start = $start->modify('+1 day');
        }

        return $days;
    }

    private static function getPeriodStart(\DateTime $date, int $periodStartDay, int $periodMonths): \DateTime
    {
        $date = clone $date; // make original immutable
        if ((int) $date->format('j') >= $periodStartDay) {
            --$periodMonths;
        }

        $date = $date->modify(sprintf('last day of -%d month midnight', $periodMonths));
        if ($periodStartDay > 28 && $date->format('j') === $date->format('t')) {
            return $date;
        }

        while ((int) $date->format('j') !== $periodStartDay) {
            $date = $date->modify('-1 day');
        }

        return $date;
    }

    /**
     * Period end is always 1 day less than period start + X months.
     */
    private static function getPeriodEnd(\DateTime $date, int $periodStartDay, int $periodMonths): \DateTime
    {
        $date = clone $date; // make original immutable

        if ($periodStartDay <= 28) {
            return $date->modify(sprintf('first day of +%s month midnight', $periodMonths))->modify(
                sprintf('+%s days', $periodStartDay - 2)
            );
        }

        return $date->modify(sprintf('last day of +%s month midnight', $periodMonths))->modify('-1 day');
    }

    private static function isLastDayOfPeriod(int $periodStartDay, int $fromDay, int $monthLength): bool
    {
        return $fromDay === $periodStartDay
            || (
                $periodStartDay === 31
                && $fromDay === $monthLength
            );
    }

    public static function getPastMonthsWindow(Service $service): int
    {
        return $service->getTariffPeriodMonths() * 6;
    }
}
