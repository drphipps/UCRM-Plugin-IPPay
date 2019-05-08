<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace SchedulingBundle\Api\Map;

use ApiBundle\Map\AbstractMap;
use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

final class JobCommentMap extends AbstractMap
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
     * @Type("integer")
     */
    public $userId;

    /**
     * @Type("DateTime")
     */
    public $createdDate;

    /**
     * @Type("string")
     */
    public $message;
}
