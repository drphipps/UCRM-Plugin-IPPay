<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Api\Map;

use ApiBundle\Map\AbstractMap;
use JMS\Serializer\Annotation\Type;

final class TicketActivityCommentMap extends AbstractMap
{
    /**
     * @Type("string")
     */
    public $body;

    /**
     * @Type("string")
     */
    public $emailFromAddress;

    /**
     * @Type("string")
     */
    public $emailFromName;

    /**
     * @Type("array<TicketingBundle\Api\Map\TicketCommentAttachmentMap>")
     */
    public $attachments = [];
}
