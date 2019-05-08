<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use AppBundle\Component\Validator\Constraints as CustomAssert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ServiceIpRepository")
 */
class ServiceIp implements NetworkDeviceIpInterface, LoggableInterface, ParentLoggableInterface
{
    use NetworkDeviceIpTrait;

    /**
     * @var ServiceDevice
     *
     * @Assert\NotNull()
     * @ORM\ManyToOne(targetEntity="ServiceDevice", inversedBy="serviceIps")
     * @ORM\JoinColumn(name="service_device_id", referencedColumnName="service_device_id", nullable=false, onDelete="CASCADE")
     */
    private $serviceDevice;

    /**
     * @ORM\Embedded(class="IpRange", columnPrefix = false)
     * @Assert\Valid()
     * @CustomAssert\IpNotUsedByService()
     * @CustomAssert\IpInInterfaceRanges(groups = {"warning"})
     *
     * @var IpRange
     */
    protected $ipRange;

    public function __construct()
    {
        $this->ipRange = new IpRange();
    }

    /**
     * Get delete message for log.
     *
     * @return array
     */
    public function getLogDeleteMessage()
    {
        $message['logMsg'] = [
            'message' => 'IP address %s deleted',
            'replacements' => $this->ipRange->getRangeForView(),
        ];

        return $message;
    }

    /**
     * Get insert message for log.
     *
     * @return array
     */
    public function getLogInsertMessage()
    {
        $message['logMsg'] = [
            'message' => 'IP address %s added',
            'replacements' => $this->ipRange->getRangeForView(),
        ];

        return $message;
    }

    /**
     * Get unloggable columns for log.
     *
     * @return array
     */
    public function getLogIgnoredColumns()
    {
        return [
            'wasLastConnectionSuccessful',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getLogClient()
    {
        return $this->getServiceDevice()->getService()->getClient();
    }

    /**
     * {@inheritdoc}
     */
    public function getLogSite()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogParentEntity()
    {
        return $this->getServiceDevice();
    }

    /**
     * {@inheritdoc}
     */
    public function getLogUpdateMessage()
    {
        $message['logMsg'] = [
            'id' => $this->getId(),
            'message' => $this->ipRange->getRangeForView(),
            'entity' => self::class,
        ];

        return $message;
    }

    /**
     * @param ServiceDevice $serviceDevice
     */
    public function setServiceDevice(ServiceDevice $serviceDevice = null): ServiceIp
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
