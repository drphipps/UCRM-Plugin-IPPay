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
 * @ORM\Table(name="device_interface_ip", indexes={@ORM\Index(name="ip_address_idx", columns={"ip_address"})})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\DeviceInterfaceIpRepository")
 */
class DeviceInterfaceIp implements NetworkDeviceIpInterface, LoggableInterface, ParentLoggableInterface
{
    use NetworkDeviceIpTrait;

    /**
     * @ORM\ManyToOne(targetEntity="DeviceInterface", inversedBy="interfaceIps")
     * @ORM\JoinColumn(name="interface_id", referencedColumnName="interface_id", nullable=false, onDelete="CASCADE")
     *
     * @var DeviceInterface
     */
    protected $interface;

    /**
     * @ORM\Column(name="is_accessible", type="boolean", options={"default":true})
     *
     * @var bool
     */
    protected $isAccessible = true;

    /**
     * @var string
     *
     * @ORM\Column(name="internal_id", type="string", length=64, nullable=true)
     * @Assert\Length(max = 64)
     */
    protected $internalId;

    /**
     * @ORM\Embedded(class="IpRange", columnPrefix = false)
     * @Assert\Valid()
     * @Assert\Expression(
     *     expression = "value.getNetmask() !== null or value.getFirstIp() === value.getLastIp()",
     *     message = "Only single IP or CIDR is allowed."
     * )
     * @CustomAssert\IpNotUsedBySameDevice()
     *
     * @var IpRange
     */
    protected $ipRange;

    public function __construct()
    {
        $this->ipRange = new IpRange();
    }

    /**
     * @return DeviceInterface
     */
    public function getInterface()
    {
        return $this->interface;
    }

    /**
     * @return $this
     */
    public function setInterface(DeviceInterface $interface)
    {
        $this->interface = $interface;

        return $this;
    }

    /**
     * Set isAccessible.
     *
     *
     * @return DeviceInterfaceIp
     */
    public function setIsAccessible(bool $isAccessible)
    {
        $this->isAccessible = $isAccessible;

        return $this;
    }

    /**
     * Get isAccessible.
     *
     * @return bool
     */
    public function getIsAccessible()
    {
        return $this->isAccessible;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
        return $this->getInterface()->getDevice()->getSite();
    }

    /**
     * {@inheritdoc}
     */
    public function getLogParentEntity()
    {
        return $this->getInterface();
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
     * Set internalId.
     *
     *
     * @return DeviceInterfaceIp
     */
    public function setInternalId(string $internalId)
    {
        $this->internalId = $internalId;

        return $this;
    }

    /**
     * Get internalId.
     *
     * @return string
     */
    public function getInternalId()
    {
        return $this->internalId;
    }
}
