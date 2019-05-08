<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace ApiBundle\Map;

use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

final class ServiceDeviceMap extends AbstractMap
{
    /**
     * @Type("integer")
     * @ReadOnly()
     */
    public $id;

    /**
     * @Type("integer")
     * @ReadOnly()
     */
    public $serviceId;

    /**
     * @Type("integer")
     */
    public $interfaceId;

    /**
     * @Type("integer")
     */
    public $vendorId;

    /**
     * @Type("string")
     */
    public $macAddress;

    /**
     * @Type("string")
     */
    public $loginUsername;

    /**
     * @Type("string")
     */
    public $loginPassword;

    /**
     * @Type("integer")
     */
    public $sshPort;

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
    public $createPingStatistics;

    /**
     * @Type("boolean")
     */
    public $createSignalStatistics;

    /**
     * @Type("array<string>")
     */
    public $ipRange;

    /**
     * @Type("integer")
     */
    public $qosEnabled;

    /**
     * @Type("array<integer>")
     */
    public $qosDeviceIds = [];
}
