<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

interface PingInterface
{
    public function getPing(): float;

    public function getPacketLoss(): float;

    public function getTime(): \DateTime;
}
