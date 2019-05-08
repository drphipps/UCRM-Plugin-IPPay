<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Geocoder;

interface GeocoderInterface
{
    public function query(string $request): ?LocationData;
}
