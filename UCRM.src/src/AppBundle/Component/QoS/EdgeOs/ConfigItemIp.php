<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\QoS\EdgeOs;

class ConfigItemIp
{
    /**
     * @var string
     */
    public $ip;

    /**
     * @var int
     */
    public $downloadNodeNumber;

    /**
     * @var int
     */
    public $uploadNodeNumber;

    /**
     * @var ConfigItemService
     */
    public $service;
}
