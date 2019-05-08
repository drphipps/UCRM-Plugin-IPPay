<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\DeviceInterfaceIp;

use AppBundle\Entity\DeviceInterfaceIp;
use Symfony\Component\EventDispatcher\Event;

class DeviceInterfaceIpDeleteEvent extends Event
{
    /**
     * @var DeviceInterfaceIp
     */
    private $deviceInterfaceIp;

    /**
     * @var int
     */
    private $id;

    public function __construct(DeviceInterfaceIp $deviceInterfaceIp, int $id)
    {
        $this->deviceInterfaceIp = $deviceInterfaceIp;
        $this->id = $id;
    }

    public function getDeviceInterfaceIp(): DeviceInterfaceIp
    {
        return $this->deviceInterfaceIp;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
