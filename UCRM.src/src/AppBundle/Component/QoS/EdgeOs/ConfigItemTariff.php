<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\QoS\EdgeOs;

class ConfigItemTariff
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var float
     */
    public $downloadSpeed;

    /**
     * @var float
     */
    public $uploadSpeed;

    /**
     * @var int
     */
    public $downloadNodeNumber;

    /**
     * @var int
     */
    public $uploadNodeNumber;
}
