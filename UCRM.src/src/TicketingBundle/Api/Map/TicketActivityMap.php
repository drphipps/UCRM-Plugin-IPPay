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

final class TicketActivityMap extends AbstractMap
{
    public const TYPE_COMMENT = 'comment';
    public const TYPE_ASSIGNMENT = 'assignment';
    public const TYPE_ASSIGNMENT_CLIENT = 'assignment_client';
    public const TYPE_ASSIGNMENT_JOB = 'assignment_job';
    public const TYPE_STATUS_CHANGE = 'status_change';

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
     * @Type("boolean")
     */
    public $public;

    /**
     * @Type("DateTime")
     */
    public $createdAt;

    /**
     * @Type("TicketingBundle\Api\Map\TicketActivityCommentMap")
     */
    public $comment;

    /**
     * @Type("TicketingBundle\Api\Map\TicketActivityUserAssignmentMap")
     */
    public $assignment;

    /**
     * @Type("TicketingBundle\Api\Map\TicketActivityClientAssignmentMap")
     */
    public $clientAssignment;

    /**
     * @Type("TicketingBundle\Api\Map\TicketActivityStatusChangeMap")
     */
    public $statusChange;

    /**
     * @Type("TicketingBundle\Api\Map\TicketActivityJobAssignmentMap")
     */
    public $jobAssignment;

    /**
     * @Type("string")
     */
    public $type;
}
