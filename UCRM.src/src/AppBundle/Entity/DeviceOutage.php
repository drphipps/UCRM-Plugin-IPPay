<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     indexes={
 *         @ORM\Index(columns={"outage_start"}),
 *     }
 * )
 */
class DeviceOutage implements DeviceOutageInterface
{
    use DeviceOutageTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="device_outage_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var Device
     *
     * @ORM\ManyToOne(targetEntity="Device", inversedBy="outages")
     * @ORM\JoinColumn(name="device_id", referencedColumnName="device_id", nullable=false, onDelete="CASCADE")
     */
    protected $device;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return DeviceOutage
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
