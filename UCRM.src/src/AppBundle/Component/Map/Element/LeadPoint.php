<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Map\Element;

class LeadPoint
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

    public function __construct(int $id, float $lat, float $lon, string $label)
    {
        $this->id = $id;
        $this->lat = $lat;
        $this->lon = $lon;
        $this->label = $label;
    }
}
