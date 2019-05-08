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
class WirelessStatisticsServiceLongTerm implements WirelessStatisticsInterface
{
    use WirelessStatisticsTrait;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time", type="date")
     */
    protected $time;

    /**
     * @var ServiceDevice
     *
     * @ORM\ManyToOne(targetEntity="ServiceDevice", inversedBy="wirelessStatisticsServiceLongTerm")
     * @ORM\JoinColumn(name="service_device_id", referencedColumnName="service_device_id", nullable=false, onDelete="CASCADE")
     */
    protected $serviceDevice;

    /**
     * @return WirelessStatisticsServiceLongTerm
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
