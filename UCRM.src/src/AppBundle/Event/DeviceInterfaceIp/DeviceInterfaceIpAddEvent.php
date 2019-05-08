<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\DeviceInterfaceIp;

use AppBundle\Entity\DeviceInterfaceIp;
use Symfony\Component\EventDispatcher\Event;

class DeviceInterfaceIpAddEvent extends Event
{
    /**
     * @var DeviceInterfaceIp
     */
    private $deviceInterfaceIp;

    public function __construct(DeviceInterfaceIp $deviceInterfaceIp)
    {
        $this->deviceInterfaceIp = $deviceInterfaceIp;
    }

    public function getDeviceInterfaceIp(): DeviceInterfaceIp
    {
        return $this->deviceInterfaceIp;
    }
}
