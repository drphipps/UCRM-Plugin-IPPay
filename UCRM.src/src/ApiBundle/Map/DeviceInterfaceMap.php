<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace ApiBundle\Map;

use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

final class DeviceInterfaceMap extends AbstractMap
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
    public $deviceId;

    /**
     * @Type("string")
     */
    public $name;

    /**
     * @Type("integer")
     */
    public $type;

    /**
     * @Type("string")
     */
    public $macAddress;

    /**
     * @Type("boolean")
     */
    public $allowClientConnection;

    /**
     * @Type("string")
     */
    public $notes;

    /**
     * @Type("boolean")
     */
    public $enabled;

    /**
     * @Type("string")
     */
    public $ssid;

    /**
     * @Type("integer")
     */
    public $frequency;

    /**
     * @Type("integer")
     */
    public $polarization;

    /**
     * @Type("integer")
     */
    public $encryptionType;

    /**
     * @Type("integer")
     */
    public $encryptionKeyWpa;

    /**
     * @Type("integer")
     */
    public $encryptionKeyWpa2;

    /**
     * @Type("array<string>")
     * @ReadOnly()
     */
    public $ipRanges = [];
}
