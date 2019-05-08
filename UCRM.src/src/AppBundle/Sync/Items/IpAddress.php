<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Sync\Items;

class IpAddress
{
    /**
     * @var string
     */
    public $ip;

    /**
     * @var int
     */
    public $netmask;

    /**
     * @var int
     */
    public $ipInt;

    /**
     * @var string
     */
    public $internalId;

    public function __toString(): string
    {
        return sprintf('%s/%d', $this->ip, $this->netmask);
    }
}
