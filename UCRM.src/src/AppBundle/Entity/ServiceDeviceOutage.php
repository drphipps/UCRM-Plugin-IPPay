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
class ServiceDeviceOutage implements DeviceOutageInterface
{
    use DeviceOutageTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="service_device_outage_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var ServiceDevice
     *
     * @ORM\ManyToOne(targetEntity="ServiceDevice", inversedBy="outages")
     * @ORM\JoinColumn(name="service_device_id", referencedColumnName="service_device_id", nullable=false, onDelete="CASCADE")
     */
    protected $serviceDevice;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getServiceDevice(): ServiceDevice
    {
        return $this->serviceDevice;
    }

    /**
     * @return $this
     */
    public function setServiceDevice(ServiceDevice $serviceDevice)
    {
        $this->serviceDevice = $serviceDevice;

        return $this;
    }
}
