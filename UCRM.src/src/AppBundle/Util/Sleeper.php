<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Util;

class Sleeper
{
    /**
     * @return int|false zero on success, or false on errors. If the call was interrupted
     *                   by a signal, sleep returns the number of seconds left
     */
    public function sleep(int $seconds)
    {
        return sleep($seconds);
    }
}
