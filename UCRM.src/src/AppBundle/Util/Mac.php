<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Util;

class Mac
{
    /**
     * @param string $macAddress
     */
    public static function format(string $macAddress = null): string
    {
        if (null === $macAddress) {
            return '';
        }

        $macAddress = str_replace(['-', ':', '.'], '', $macAddress);

        return strtoupper($macAddress);
    }

    /**
     * @param string $macAddress
     */
    public static function formatView(string $macAddress = null): string
    {
        if (null === $macAddress) {
            return '';
        }

        $macAddress = self::format($macAddress);

        return substr(chunk_split($macAddress, 2, ':'), 0, -1);
    }
}
