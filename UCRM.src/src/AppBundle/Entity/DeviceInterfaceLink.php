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
class DeviceInterfaceLink
{
    /**
     * @var int
     *
     * @ORM\Column(name="link_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var DeviceInterface
     *
     * @ORM\ManyToOne(targetEntity="DeviceInterface")
     * @ORM\JoinColumn(name="interface_a", referencedColumnName="interface_id", nullable=false)
     */
    private $interfaceA;

    /**
     * @var DeviceInterface
     *
     * @ORM\ManyToOne(targetEntity="DeviceInterface")
     * @ORM\JoinColumn(name="interface_b", referencedColumnName="interface_id", nullable=false)
     */
    private $interfaceB;

    /**
     * @return DeviceInterface
     */
    public function getInterfaceA()
    {
        return $this->interfaceA;
    }

    /**
     * @return $this
     */
    public function setInterfaceA(DeviceInterface $interfaceA)
    {
        $this->interfaceA = $interfaceA;

        return $this;
    }

    /**
     * @return DeviceInterface
     */
    public function getInterfaceB()
    {
        return $this->interfaceB;
    }

    /**
     * @return $this
     */
    public function setInterfaceB(DeviceInterface $interfaceB)
    {
        $this->interfaceB = $interfaceB;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
