<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Sync\Items;

use AppBundle\Entity\DeviceInterface;

class NetworkInterface
{
    /**
     * @var string|null
     */
    public $macAddress;

    /**
     * @var int
     */
    public $internalId;

    /**
     * @var string
     */
    public $internalName;

    /**
     * @var string|int
     */
    public $internalType;

    /**
     * @var array|IpAddress[]
     */
    public $addresses = [];

    /**
     * @var DeviceInterface
     */
    public $matchedDeviceInterface;
}
