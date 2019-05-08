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

final class TicketCommentMap extends AbstractMap
{
    /**
     * @Type("integer")
     * @ReadOnly()
     */
    public $id;

    /**
     * @Type("integer")
     */
    public $ticketId;

    /**
     * @Type("integer")
     */
    public $userId;

    /**
     * @Type("string")
     */
    public $body;

    /**
     * @Type("boolean")
     */
    public $public;

    /**
     * @Type("DateTime")
     */
    public $createdAt;

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
