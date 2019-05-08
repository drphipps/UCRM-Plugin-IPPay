<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\DeviceInterface;

use AppBundle\Entity\DeviceInterface;
use Symfony\Component\EventDispatcher\Event;

class DeviceInterfaceArchiveEvent extends Event
{
    /**
     * @var DeviceInterface
     */
    private $deviceInterface;

    public function __construct(DeviceInterface $deviceInterface)
    {
        $this->deviceInterface = $deviceInterface;
    }

    public function getDeviceInterface(): DeviceInterface
    {
        return $this->deviceInterface;
    }
}
