<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ServiceDeviceLogRepository")
 */
class ServiceDeviceLog implements NetworkDeviceLogInterface
{
    use NetworkDeviceLogTrait;

    /**
     * @var ServiceDevice
     *
     * @ORM\ManyToOne(targetEntity="ServiceDevice")
     * @ORM\JoinColumn(name="service_device_id", referencedColumnName="service_device_id", onDelete="CASCADE")
     */
    protected $serviceDevice;

    /**
     * @return ServiceDeviceLog
     */
    public function setServiceDevice(ServiceDevice $serviceDevice)
    {
        $this->serviceDevice = $serviceDevice;

        return $this;
    }

    /**
     * @return ServiceDevice
     */
    public function getServiceDevice()
    {
        return $this->serviceDevice;
    }
}
