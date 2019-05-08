<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class PingLongTerm implements PingInterface
{
    use PingTrait;

    /**
     * @var Device
     *
     * @ORM\ManyToOne(targetEntity="Device")
     * @ORM\JoinColumn(referencedColumnName="device_id", nullable=false, onDelete="CASCADE")
     */
    protected $device;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time", type="date")
     */
    protected $time;

    public function getDevice(): Device
    {
        return $this->device;
    }

    /**
     * @return $this
     */
    public function setDevice(Device $device)
    {
        $this->device = $device;

        return $this;
    }

    public function getTime(): \DateTime
    {
        return $this->time;
    }

    /**
     * @return $this
     */
    public function setTime(\DateTime $time)
    {
        $this->time = $time;

        return $this;
    }
}
