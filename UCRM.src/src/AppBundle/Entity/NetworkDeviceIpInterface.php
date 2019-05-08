<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

interface NetworkDeviceIpInterface
{
    public function getIpRange(): IpRange;

    public function getWasLastConnectionSuccessful(): bool;

    /**
     * @return self
     */
    public function setWasLastConnectionSuccessful(bool $wasLastConnectionSuccessful);
}
