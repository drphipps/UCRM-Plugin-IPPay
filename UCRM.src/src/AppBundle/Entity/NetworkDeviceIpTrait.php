<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait NetworkDeviceIpTrait
{
    /**
     * @var int
     *
     * @ORM\Column(name="ip_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @ORM\Embedded(class="IpRange", columnPrefix = false)
     * @Assert\Valid()
     *
     * @var IpRange
     */
    protected $ipRange;

    /**
     * @var int
     *
     * @ORM\Column(name="nat_public_ip", type="bigint", nullable=true)
     */
    protected $natPublicIp;

    /**
     * @var bool
     *
     * @ORM\Column(name="was_last_connection_successful", type="boolean", options={"default":false})
     */
    protected $wasLastConnectionSuccessful = false;

    /**
     * Needed for synchronization.
     */
    public function __clone()
    {
        $this->ipRange = clone $this->ipRange;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getIpRange(): IpRange
    {
        return $this->ipRange;
    }

    /**
     * @return int
     */
    public function getNatPublicIp()
    {
        return $this->natPublicIp;
    }

    /**
     * @param int $natPublicIp
     *
     * @return self
     */
    public function setNatPublicIp(int $natPublicIp = null)
    {
        $this->natPublicIp = $natPublicIp;

        return $this;
    }

    public function getWasLastConnectionSuccessful(): bool
    {
        return $this->wasLastConnectionSuccessful;
    }

    /**
     * @return self
     */
    public function setWasLastConnectionSuccessful(bool $wasLastConnectionSuccessful)
    {
        $this->wasLastConnectionSuccessful = $wasLastConnectionSuccessful;

        return $this;
    }
}
