<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 */
class SearchDeviceQueue
{
    /**
     * @var Device
     *
     * @ORM\Id()
     * @ORM\OneToOne(targetEntity="Device")
     * @ORM\JoinColumn(name="device_id", referencedColumnName="device_id", nullable=false, onDelete="CASCADE")
     * @Assert\NotNull()
     */
    protected $device;

    /**
     * @return SearchDeviceQueue
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
