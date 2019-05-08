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
class DeviceIp implements NetworkDeviceIpInterface
{
    use NetworkDeviceIpTrait;

    /**
     * @ORM\Embedded(class="IpRange", columnPrefix = false)
     * @Assert\Valid()
     * @Assert\Expression(
     *     expression = "value.getNetmask() !== null or value.getFirstIp() === value.getLastIp()",
     *     message = "Only single IP or CIDR is allowed."
     * )
     *
     * @var IpRange
     */
    protected $ipRange;

    public function __construct()
    {
        $this->ipRange = new IpRange();
    }

    /**
     * Compares without netmask. Needed for sync IP accessible matching.
     */
    public function isIpEqualTo(NetworkDeviceIpInterface $networkDeviceIp): bool
    {
        return $networkDeviceIp->getIpRange()->getIpAddress() === $this->ipRange->getIpAddress();
    }
}
