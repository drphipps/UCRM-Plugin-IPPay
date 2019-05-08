<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Util;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use InvalidArgumentException;

class DateTimeImmutableFactory
{
    /**
     * @param string $date in "YYYY-MM-DD" format
     *
     * @throws InvalidArgumentException
     */
    public static function createDate(string $date): DateTimeImmutable
    {
        return self::createFromFormat('Y-m-d|', $date);
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function createFromFormat(
        string $format,
        string $dateTime,
        ?DateTimeZone $timezone = null
    ): DateTimeImmutable {
        $dateTime = DateTimeImmutable::createFromFormat($format, $dateTime, $timezone);
        if ($dateTime === false) {
            throw new InvalidArgumentException('The parsed date was invalid.');
        }

        self::assertNoErrors();

        return $dateTime;
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function createWithoutFormat(string $dateTime, ?DateTimeZone $timezone = null): DateTimeImmutable
    {
        try {
            $dateTime = new DateTimeImmutable($dateTime, $timezone);
        } catch (Exception $e) {
            throw new InvalidArgumentException('The parsed date was invalid.');
        }

        self::assertNoErrors();

        return $dateTime;
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function createDateFromUTC(string $dateTime, string $dateTimeZone): DateTimeImmutable
    {
        $dateTime = self::createFromFormat(
            'Y-m-d H:i:s',
            $dateTime,
            new DateTimeZone('UTC')
        );

        return $dateTime->setTimezone(new DateTimeZone($dateTimeZone));
    }

    public static function createFromInterface(DateTimeInterface $dateTime): DateTimeImmutable
    {
        if ($dateTime instanceof DateTimeImmutable) {
            return $dateTime;
        }

        return self::createFromFormat(
            DateTime::ISO8601,
            $dateTime->format(DateTime::ISO8601),
            $dateTime->getTimezone()
        );
    }

    public static function createFromUnknownFormat(string $dateTime): ?DateTimeImmutable
    {
        try {
            return self::createFromFormat(DateTimeImmutable::ATOM, $dateTime);
        } catch (InvalidArgumentException $e) {
        }

        try {
            return self::createDate($dateTime);
        } catch (InvalidArgumentException $e) {
        }

        try {
            return self::createWithoutFormat($dateTime);
        } catch (InvalidArgumentException $e) {
        }

        return null;
    }

    public static function createDateFromNumbers(int $year, int $month, int $day): DateTimeImmutable
    {
        assert(checkdate($month, $day, $year));

        return self::createDate(
            sprintf(
                '%\'04d-%\'02d-%\'02d',
                $year,
                $month,
                $day
            )
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function assertNoErrors(): void
    {
        $errors = DateTimeImmutable::getLastErrors();

        if ($errors['error_count'] > 0) {
            throw new InvalidArgumentException(implode(PHP_EOL, $errors['warnings']));
        }

        if ($errors['warning_count'] > 0) {
            throw new InvalidArgumentException(implode(PHP_EOL, $errors['warnings']));
        }
    }
}
