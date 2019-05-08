<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace SchedulingBundle\Api\Map;

use ApiBundle\Map\AbstractMap;
use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

final class JobMap extends AbstractMap
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
     * @Type("integer")
     */
    public $assignedUserId;

    /**
     * @Type("integer")
     */
    public $clientId;

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

    /**
     * @Type("array<SchedulingBundle\Api\Map\JobAttachmentMap>")
     */
    public $attachments = [];

    /**
     * @Type("array<SchedulingBundle\Api\Map\JobTaskMap>")
     */
    public $tasks = [];
}
