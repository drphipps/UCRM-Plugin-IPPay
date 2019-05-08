<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\VendorRepository")
 */
class Vendor implements LoggableInterface, ParentLoggableInterface
{
    const VENDOR_MAX_SYSTEM_ID = 1000;
    const EDGE_OS = 1;
    const ROUTER_OS = 2;
    const AIR_OS = 3;

    const SYNCHRONIZED_VENDORS = [
        // new vendors need to be added to implementations of BaseDevice::getDriverClassName()
        self::EDGE_OS,
        self::AIR_OS,
        self::ROUTER_OS,
    ];

    // Vendors of network devices where the Suspension feature is supported
    const SUSPEND_VENDORS = [
        self::EDGE_OS,
        self::ROUTER_OS,
    ];

    // Vendors of network devices where the QoS set up is supported (On CPE's AirOS is supported as well)
    const QOS_VENDORS = [
        self::EDGE_OS,
    ];

    const TYPES = [
        self::EDGE_OS => 'Ubiquiti Networks EdgeOS',
        self::AIR_OS => 'Ubiquiti Networks airOS',
        self::ROUTER_OS => 'Mikrotik RouterOS',
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="vendor_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50)
     * @Assert\Length(max = 50)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @var Collection|Device[]
     *
     * @ORM\OneToMany(targetEntity="Device", mappedBy="vendor")
     * @ORM\JoinColumn(name="vendor_id", referencedColumnName="vendor_id")
     */
    private $devices;

    /**
     * @var Collection|ServiceDevice[]
     *
     * @ORM\OneToMany(targetEntity="ServiceDevice", mappedBy="vendor")
     * @ORM\JoinColumn(name="vendor_id", referencedColumnName="vendor_id")
     */
    private $serviceDevices;

    public function __construct()
    {
        $this->devices = new ArrayCollection();
        $this->serviceDevices = new ArrayCollection();
    }

    /**
     * @return Collection|Device[]
     */
    public function getDevices()
    {
        return $this->devices;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     *
     * @return Vendor
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Vendor
     */
    public function addDevice(Device $device)
    {
        $this->devices[] = $device;

        return $this;
    }

    public function removeDevice(Device $device)
    {
        $this->devices->removeElement($device);
    }

    /**
     * Get delete message for log.
     *
     * @return array
     */
    public function getLogDeleteMessage()
    {
        $message['logMsg'] = [
            'message' => 'Vendor %s deleted',
            'replacements' => $this->getName(),
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
            'message' => 'Vendor %s added',
            'replacements' => $this->getName(),
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
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getLogClient()
    {
        return null;
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
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogUpdateMessage()
    {
        $message['logMsg'] = [
            'id' => $this->getId(),
            'message' => $this->getName(),
            'entity' => self::class,
        ];

        return $message;
    }

    /**
     * @return Vendor
     */
    public function addServiceDevice(ServiceDevice $serviceDevice)
    {
        $this->serviceDevices[] = $serviceDevice;

        return $this;
    }

    public function removeServiceDevice(ServiceDevice $serviceDevice)
    {
        $this->serviceDevices->removeElement($serviceDevice);
    }

    /**
     * @return Collection|ServiceDevice[]
     */
    public function getServiceDevices()
    {
        return $this->serviceDevices;
    }
}
