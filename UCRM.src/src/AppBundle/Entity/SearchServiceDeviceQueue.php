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
class SearchServiceDeviceQueue
{
    /**
     * @var ServiceDevice
     *
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="ServiceDevice")
     * @ORM\JoinColumn(name="device_id", referencedColumnName="service_device_id", nullable=false, onDelete="CASCADE")
     * @Assert\NotNull()
     */
    protected $serviceDevice;

    /**
     * Set serviceDevice.
     *
     *
     * @return SearchServiceDeviceQueue
     */
    public function setServiceDevice(ServiceDevice $serviceDevice)
    {
        $this->serviceDevice = $serviceDevice;

        return $this;
    }

    /**
     * Get serviceDevice.
     *
     * @return ServiceDevice
     */
    public function getServiceDevice()
    {
        return $this->serviceDevice;
    }
}
