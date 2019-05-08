<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\DeviceInterface;

use AppBundle\Entity\DeviceInterface;
use Symfony\Component\EventDispatcher\Event;

class DeviceInterfaceEditEvent extends Event
{
    /**
     * @var DeviceInterface
     */
    private $deviceInterface;

    /**
     * @var DeviceInterface
     */
    private $deviceInterfaceBeforeUpdate;

    public function __construct(DeviceInterface $deviceInterface, DeviceInterface $deviceInterfaceBeforeUpdate)
    {
        $this->deviceInterface = $deviceInterface;
        $this->deviceInterfaceBeforeUpdate = $deviceInterfaceBeforeUpdate;
    }

    public function getDeviceInterface(): DeviceInterface
    {
        return $this->deviceInterface;
    }

    public function getDeviceInterfaceBeforeUpdate(): DeviceInterface
    {
        return $this->deviceInterfaceBeforeUpdate;
    }
}
