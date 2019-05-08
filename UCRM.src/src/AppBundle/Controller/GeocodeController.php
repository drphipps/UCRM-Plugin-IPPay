<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Geocoder\Nominatim\NominatimGeocoder;
use AppBundle\Security\Permission;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class GeocodeController extends BaseController
{
    /**
     * @Route("/geocode", name="geocode_address", options={"expose"=true})
     * @Method("POST")
     * @Permission("guest")
     */
    public function geocodeAction(Request $request): JsonResponse
    {
        $address = $request->request->get('address');
        if (! is_string($address)) {
            throw $this->createNotFoundException();
        }

        try {
            $gps = $this->get(NominatimGeocoder::class)->query($address);
        } catch (\RuntimeException $exception) {
            $gps = null;
        }

        return new JsonResponse(
            [
                'lat' => $gps ? $gps->lat : null,
                'lon' => $gps ? $gps->lon : null,
            ]
        );
    }
}
