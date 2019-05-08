<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\DeviceLogRepository")
 */
class DeviceLog implements NetworkDeviceLogInterface
{
    use NetworkDeviceLogTrait;

    /**
     * @var Device
     *
     * @ORM\ManyToOne(targetEntity="Device")
     * @ORM\JoinColumn(name="device_id", referencedColumnName="device_id", onDelete="CASCADE")
     */
    protected $device;

    /**
     * @return DeviceLog
     */
    public function setDevice(Device $device)
    {
        $this->device = $device;

        return $this;
    }

    /**
     * @return Device
     */
    public function getDevice()
    {
        return $this->device;
    }
}
