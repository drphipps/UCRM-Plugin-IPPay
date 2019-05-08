<?php

declare(strict_types=1);

/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Util;

use Nette\Utils\Strings;

class IpRangeParser
{
    const FORMAT_SINGLE = 1;
    const FORMAT_CIDR = 2;
    const FORMAT_RANGE = 3;

    const PATTERN_CIDR = '~^(25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])(?:\.(?1)){3}\s*+\/\s*+(?:3[0-2]|[1-2]?[0-9])$~s';
    const PATTERN_RANGE_LONG_REGEX = '~^(25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])(\.(?1)){3}\s*+-\s*+(?1)(?2){3}$~s';
    const PATTERN_RANGE_SHORT_REGEX = '~^(25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])(?:\.(?1)){3}\s*+-\s*+(?1)$~s';
    const PATTERN_SINGLE = '~^(25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])(?:\.(?1)){3}$~s';

    /**
     * @return \stdClass|null
     */
    public static function parse(string $value)
    {
        switch (true) {
            case Strings::match($value, self::PATTERN_SINGLE):
                $ip = ip2long($value);
                if ($ip === false) {
                    return null;
                }

                return (object) [
                    'type' => self::FORMAT_SINGLE,
                    'ip' => $ip,
                ];

            case Strings::match($value, self::PATTERN_CIDR):
                $parts = explode('/', $value);
                $ip = ip2long(Strings::trim($parts[0]));
                if ($ip === false) {
                    return null;
                }
                $netmask = (int) Strings::trim($parts[1]);

                return (object) [
                    'type' => self::FORMAT_CIDR,
                    'ip' => $ip,
                    'netmask' => $netmask,
                ];

            case Strings::match($value, self::PATTERN_RANGE_LONG_REGEX):
                $parts = explode('-', $value);
                $first = ip2long(Strings::trim($parts[0]));
                $last = ip2long(Strings::trim($parts[1]));

                if ($first === false || $last === false || $first > $last) {
                    return null;
                }

                return (object) [
                    'type' => self::FORMAT_RANGE,
                    'first' => $first,
                    'last' => $last,
                ];

            case Strings::match($value, self::PATTERN_RANGE_SHORT_REGEX):
                $parts = explode('-', $value);
                $first = ip2long(Strings::trim($parts[0]));
                // The byte shifts set the last part of IP (after third dot) to zero.
                $last = ($first >> 8 << 8) + (int) Strings::trim($parts[1]);

                if ($first === false || $first > $last) {
                    return null;
                }

                return (object) [
                    'type' => self::FORMAT_RANGE,
                    'first' => $first,
                    'last' => $last,
                ];
        }

        return null;
    }

    public static function isSingleOrCidrIpAddress(string $value): bool
    {
        return Strings::match($value, self::PATTERN_SINGLE)
            || Strings::match($value, self::PATTERN_CIDR);
    }
}
