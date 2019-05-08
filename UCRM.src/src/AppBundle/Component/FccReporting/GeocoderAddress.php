<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\FccReporting;

class GeocoderAddress
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $street;

    /**
     * @var string|null
     */
    public $city;

    /**
     * @var string|null
     */
    public $state;

    /**
     * @var string|null
     */
    public $zip;

    /**
     * @var float|null
     */
    public $gpsLat;

    /**
     * @var float|null
     */
    public $gpsLon;

    public function __construct(
        int $id,
        string $street,
        ?string $city,
        ?string $state,
        ?string $zip,
        ?float $gpsLat,
        ?float $gpsLon
    ) {
        $this->id = $id;
        $this->street = $street;
        $this->city = $city;
        $this->state = $state;
        $this->zip = $zip;
        $this->gpsLat = $gpsLat;
        $this->gpsLon = $gpsLon;
    }

    public function toGeocoderArray(): array
    {
        return [
            'id' => $this->id,
            'street' => $this->street,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
        ];
    }
}
