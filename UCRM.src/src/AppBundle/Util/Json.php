<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Util;

use Nette\Utils\JsonException;

class Json
{
    public static function decodeJsonLeaveString(string $string)
    {
        try {
            return \Nette\Utils\Json::decode($string, \Nette\Utils\Json::FORCE_ARRAY);
        } catch (JsonException $exception) {
            return $string;
        }
    }
}
