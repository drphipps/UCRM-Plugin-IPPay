<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace ApiBundle\Map;

use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

final class SiteMap extends AbstractMap
{
    /**
     * @Type("integer")
     * @ReadOnly()
     */
    public $id;

    /**
     * @Type("string")
     */
    public $name;

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

    /**
     * @Type("string")
     */
    public $contactInfo;

    /**
     * @Type("string")
     */
    public $notes;
}
