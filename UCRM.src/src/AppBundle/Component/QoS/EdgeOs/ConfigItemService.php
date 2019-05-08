<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\QoS\EdgeOs;

class ConfigItemService
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $downloadNodeNumber;

    /**
     * @var int
     */
    public $uploadNodeNumber;

    /**
     * @var float
     */
    public $downloadSpeed;

    /**
     * @var float
     */
    public $uploadSpeed;

    /**
     * @var ConfigItemTariff
     */
    public $tariff;
}
