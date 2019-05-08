<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\AirLink;

use Location\Coordinate;

class AirLinkUrlGenerator
{
    private const BASE_URL = 'https://link.ubnt.com/#';

    private const AP_TEMPLATE = [
        'ap.location.lat' => '',
        'ap.location.lng' => '',
        'ap.device.antennaGain' => '25',
        'ap.device.channelWidth' => '20',
        'ap.device.eirp' => '50',
        'ap.device.frequency' => '5600',
        'ap.device.name' => 'PowerBeam 5AC Gen2',
        'ap.height' => '18',
        'coverageCpeHeight' => '12',
        'coverageRadius' => '20000',
        'mapTypeId' => 'hybrid',
        'version' => '1.0.2',
    ];

    private const CPE_TEMPLATE = [
        'cpeList.<number>.location.lat' => '<lat>',
        'cpeList.<number>.location.lng' => '<lng>',
        'cpeList.<number>.device.antennaGain' => '23',
        'cpeList.<number>.device.eirp' => '47',
        'cpeList.<number>.height' => '12',
    ];

    /**
     * @param Coordinate[] $cpeList
     */
    public function get(Coordinate $ap, array $cpeList): string
    {
        $params = self::AP_TEMPLATE;
        $params['ap.location.lat'] = (string) $ap->getLat();
        $params['ap.location.lng'] = (string) $ap->getLng();

        $i = 0;
        foreach ($cpeList as $cpe) {
            foreach (self::CPE_TEMPLATE as $key => $value) {
                $params[strtr($key, ['<number>' => $i])] = strtr(
                    $value,
                    [
                        '<lat>' => $cpe->getLat(),
                        '<lng>' => $cpe->getLng(),
                    ]
                );
            }

            ++$i;
        }

        return self::BASE_URL . http_build_query($params);
    }
}
