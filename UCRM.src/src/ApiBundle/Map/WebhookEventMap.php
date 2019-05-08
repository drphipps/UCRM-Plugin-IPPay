<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Map;

use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

final class WebhookEventMap extends AbstractMap
{
    /**
     * @Type("string")
     * @ReadOnly()
     */
    public $uuid;

    /**
     * @Type("string")
     * @ReadOnly()
     */
    public $changeType;

    /**
     * @Type("string")
     * @ReadOnly()
     */
    public $entity;

    /**
     * @Type("integer")
     * @ReadOnly()
     */
    public $entityId;

    /**
     * @Type("string")
     * @ReadOnly()
     */
    public $eventName;

    /**
     * @Type("array")
     * @ReadOnly()
     */
    public $extraData;
}
