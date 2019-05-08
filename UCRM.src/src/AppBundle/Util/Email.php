<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Util;

class Email
{
    /**
     * @param string|array $emailAddress
     */
    public static function formatView($emailAddress = null): string
    {
        if (is_string($emailAddress)) {
            return $emailAddress;
        }

        if (is_array($emailAddress)) {
            $response = [];
            foreach ($emailAddress as $address => $name) {
                $response[] = $name ? $name . ' <' . $address . '>' : $address;
            }

            return $response ? implode(', ', $response) : '';
        }

        return '';
    }
}
