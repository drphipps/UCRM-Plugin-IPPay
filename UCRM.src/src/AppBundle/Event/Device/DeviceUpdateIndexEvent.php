<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Device;

use AppBundle\Entity\Device;
use Symfony\Component\EventDispatcher\Event;

class DeviceUpdateIndexEvent extends Event
{
    /**
     * @var Device
     */
    private $device;

    public function __construct(Device $device)
    {
        $this->device = $device;
    }

    public function getDevice(): Device
    {
        return $this->device;
    }
}
