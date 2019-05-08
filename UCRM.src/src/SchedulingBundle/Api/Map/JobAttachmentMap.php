<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Api\Map;

use ApiBundle\Map\AbstractMap;
use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

final class JobAttachmentMap extends AbstractMap
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
    public $file;

    /**
     * @Type("string")
     */
    public $filename;

    /**
     * @Type("string")
     * @ReadOnly()
     */
    public $mimeType;

    /**
     * @Type("integer")
     * @ReadOnly()
     */
    public $size;
}
