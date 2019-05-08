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
class PingServiceShortTerm implements PingInterface
{
    use PingTrait;

    /**
     * @var ServiceDevice
     *
     * @ORM\ManyToOne(targetEntity="ServiceDevice")
     * @ORM\JoinColumn(referencedColumnName="service_device_id", nullable=false, onDelete="CASCADE")
     */
    protected $device;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time", type="datetimetz")
     */
    protected $time;

    public function getDevice(): ServiceDevice
    {
        return $this->device;
    }

    /**
     * @return $this
     */
    public function setDevice(ServiceDevice $device)
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
