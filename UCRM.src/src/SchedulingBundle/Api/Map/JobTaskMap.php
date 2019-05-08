<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace SchedulingBundle\Api\Map;

use ApiBundle\Map\AbstractMap;
use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

final class JobTaskMap extends AbstractMap
{
    /**
     * @Type("integer")
     * @ReadOnly()
     */
    public $id;

    /**
     * @Type("integer")
     */
    public $jobId;

    /**
     * @Type("string")
     */
    public $label;

    /**
     * @Type("boolean")
     */
    public $closed;
}
