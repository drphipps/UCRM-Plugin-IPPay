<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Geocoder;

use AppBundle\Component\Geocoder\Google\GoogleGeocoder;
use AppBundle\Component\Geocoder\Google\GoogleGeocodingException;
use AppBundle\Component\Geocoder\Nominatim\NominatimGeocoder;
use AppBundle\Entity\Option;
use AppBundle\Service\Options;

class Geocoder
{
    /**
     * @var Options
     */
    private $options;

    /**
     * @var NominatimGeocoder
     */
    private $nominatimGeocoder;

    /**
     * @var GoogleGeocoder
     */
    private $googleGeocoder;

    public function __construct(Options $options, NominatimGeocoder $nominatimGeocoder, GoogleGeocoder $googleGeocoder)
    {
        $this->options = $options;
        $this->nominatimGeocoder = $nominatimGeocoder;
        $this->googleGeocoder = $googleGeocoder;
    }

    /**
     * @throws GoogleGeocodingException
     * @throws \RuntimeException
     */
    public function geocodeAddress(string $address): ?LocationData
    {
        if (
            $this->options->get(Option::GOOGLE_API_KEY)
            && ($response = $this->googleGeocoder->query($address))
        ) {
            return $response;
        }

        return $this->nominatimGeocoder->query($address);
    }
}
