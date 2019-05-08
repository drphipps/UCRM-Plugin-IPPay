<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace SchedulingBundle\Api\Map;

use ApiBundle\Map\AbstractMap;
use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

final class PublicJobMap extends AbstractMap
{
    /**
     * @Type("integer")
     * @ReadOnly()
     */
    public $id;

    /**
     * @Type("string")
     */
    public $title;

    /**
     * @Type("string")
     */
    public $description;

    /**
     * @Type("DateTime")
     */
    public $date;

    /**
     * @Type("integer")
     */
    public $duration;

    /**
     * @Type("integer")
     */
    public $status;

    /**
     * @Type("string")
     */
    public $address;

    /**
     * @Type("string")
     */
    public $gpsLat;

    /**
     * @Type("string")
     */
    public $gpsLon;
}
