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
class WirelessStatisticsShortTerm implements WirelessStatisticsInterface
{
    use WirelessStatisticsTrait;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time", type="datetimetz")
     */
    protected $time;

    /**
     * @var Device
     *
     * @ORM\ManyToOne(targetEntity="Device", inversedBy="wirelessStatisticsShortTerm")
     * @ORM\JoinColumn(name="device_id", referencedColumnName="device_id", nullable=false, onDelete="CASCADE")
     */
    protected $device;

    /**
     * @return WirelessStatisticsShortTerm
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
