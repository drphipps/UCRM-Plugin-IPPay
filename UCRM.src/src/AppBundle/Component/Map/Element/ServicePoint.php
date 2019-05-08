<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Map\Element;

class ServicePoint
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var float
     */
    public $lat;

    /**
     * @var float
     */
    public $lon;

    /**
     * @var string
     */
    public $label;

    /**
     * @var array
     */
    public $siteIds;

    /**
     * @var string
     */
    public $icon;

    public function __construct(int $id, float $lat, float $lon, string $label, array $siteIds, string $icon = 'service')
    {
        $this->id = $id;
        $this->lat = $lat;
        $this->lon = $lon;
        $this->label = $label;
        $this->siteIds = $siteIds;
        $this->icon = $icon;
    }
}
