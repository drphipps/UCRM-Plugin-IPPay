<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Factory\Fcc;

use AppBundle\Component\FccReporting\GeocoderAddress;
use AppBundle\Entity\Service;

class GeocoderAddressFactory
{
    public function create(Service $service): GeocoderAddress
    {
        return new GeocoderAddress(
            $service->getId(),
            $service->getStreet1(),
            $service->getCity(),
            $service->getState() ? $service->getState()->getCode() : null,
            $service->getZipCode(),
            $service->getAddressGpsLat(),
            $service->getAddressGpsLon()
        );
    }
}
