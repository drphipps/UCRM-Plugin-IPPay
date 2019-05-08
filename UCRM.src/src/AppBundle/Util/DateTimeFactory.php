<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Util;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use InvalidArgumentException;

class DateTimeFactory
{
    /**
     * @param string $date in "YYYY-MM-DD" format
     *
     * @throws InvalidArgumentException
     */
    public static function createDate(string $date): DateTime
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
    ): DateTime {
        $dateTime = DateTime::createFromFormat($format, $dateTime, $timezone);
        if ($dateTime === false) {
            throw new InvalidArgumentException('The parsed date was invalid.');
        }

        self::assertNoErrors();

        return $dateTime;
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function createWithoutFormat(string $dateTime, ?DateTimeZone $timezone = null): DateTime
    {
        try {
            $dateTime = new DateTime($dateTime, $timezone);
        } catch (Exception $e) {
            throw new InvalidArgumentException('The parsed date was invalid.');
        }

        self::assertNoErrors();

        return $dateTime;
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function createDateFromUTC(string $dateTime, string $dateTimeZone): DateTime
    {
        $dateTime = self::createFromFormat(
            'Y-m-d H:i:s',
            $dateTime,
            new DateTimeZone('UTC')
        );

        return $dateTime->setTimezone(new DateTimeZone($dateTimeZone));
    }

    public static function createFromInterface(DateTimeInterface $dateTime): DateTime
    {
        return self::createFromFormat(
            DateTime::ISO8601,
            $dateTime->format(DateTime::ISO8601),
            $dateTime->getTimezone()
        );
    }

    public static function createFromUnknownFormat(string $dateTime): ?DateTime
    {
        try {
            return self::createFromFormat(DateTime::ATOM, $dateTime);
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

    /**
     * @throws InvalidArgumentException
     */
    private static function assertNoErrors(): void
    {
        $errors = DateTime::getLastErrors();

        if ($errors['error_count'] > 0) {
            throw new InvalidArgumentException(implode(PHP_EOL, $errors['warnings']));
        }

        if ($errors['warning_count'] > 0) {
            throw new InvalidArgumentException(implode(PHP_EOL, $errors['warnings']));
        }
    }
}
