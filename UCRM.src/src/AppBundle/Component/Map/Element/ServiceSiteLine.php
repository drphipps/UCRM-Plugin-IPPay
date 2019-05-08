<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Map\Element;

class ServiceSiteLine
{
    /**
     * @var array
     */
    public $service;

    /**
     * @var array
     */
    public $site;

    public function __construct(ServicePoint $service, SitePoint $site)
    {
        $this->service = [
            'id' => $service->id,
            'lat' => $service->lat,
            'lon' => $service->lon,
        ];

        $this->site = [
            'id' => $site->id,
            'lat' => $site->lat,
            'lon' => $site->lon,
        ];
    }
}
