<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Geocoder;

class LocationData
{
    /**
     * @var float|null
     */
    public $lat;

    /**
     * @var float|null
     */
    public $lon;

    /**
     * @var string
     */
    public $address;
}
