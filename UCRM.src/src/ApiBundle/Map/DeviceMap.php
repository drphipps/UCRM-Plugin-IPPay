<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace ApiBundle\Map;

use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

final class DeviceMap extends AbstractMap
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
     * @Type("integer")
     */
    public $siteId;

    /**
     * @Type("integer")
     */
    public $vendorId;

    /**
     * @Type("string")
     */
    public $modelName;

    /**
     * @Type("array<integer>")
     */
    public $parentIds = [];

    /**
     * @Type("string")
     */
    public $notes;

    /**
     * @Type("string")
     */
    public $loginUsername;

    /**
     * @Type("integer")
     */
    public $sshPort;

    /**
     * @Type("string")
     */
    public $snmpCommunity;

    /**
     * @Type("string")
     */
    public $osVersion;

    /**
     * @Type("boolean")
     */
    public $isGateway;

    /**
     * @Type("boolean")
     */
    public $isSuspendEnabled;

    /**
     * @Type("boolean")
     */
    public $sendPingNotifications;

    /**
     * @Type("integer")
     */
    public $pingNotificationUserId;

    /**
     * @Type("boolean")
     */
    public $createSignalStatistics;
}
