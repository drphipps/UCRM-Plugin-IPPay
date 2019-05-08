<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Api\Map;

use ApiBundle\Map\AbstractMap;
use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

final class TicketCommentAttachmentMap extends AbstractMap
{
    /**
     * @Type("integer")
     * @ReadOnly()
     */
    public $id;

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
     */
    public $mimeType;

    /**
     * @Type("integer")
     */
    public $size;
}
