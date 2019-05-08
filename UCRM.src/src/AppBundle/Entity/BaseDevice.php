<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\MappedSuperclass;

/**
 * @MappedSuperclass()
 */
abstract class BaseDevice
{
    use NetworkDeviceTrait;
    use PingableDeviceTrait;

    const DEFAULT_SSH_PORT = 22;

    const STATUS_UNKNOWN = 0;
    const STATUS_ONLINE = 1;
    const STATUS_DOWN = 2;
    const STATUS_UNREACHABLE = 3;

    const STATUS = [
        self::STATUS_UNKNOWN => 'unknown',
        self::STATUS_ONLINE => 'online',
        self::STATUS_DOWN => 'down',
        self::STATUS_UNREACHABLE => 'unreachable',
    ];

    const OFFLINE_STATUSES = [
        self::STATUS_DOWN,
        self::STATUS_UNREACHABLE,
    ];

    const QOS_DISABLED = 0;
    const QOS_THIS = 1;
    const QOS_ANOTHER = 2;

    const QOS_TYPES = [
        self::QOS_DISABLED => 'no',
        self::QOS_THIS => 'on this device',
        self::QOS_ANOTHER => 'on another device(s)',
    ];

    const POSSIBLE_QOS_TYPES = [
        self::QOS_DISABLED,
        self::QOS_THIS,
        self::QOS_ANOTHER,
    ];

    /**
     * @return int
     */
    abstract public function getId();

    abstract public function getBackupDirectory(): string;

    abstract public function getDriverClassName(): string;

    /**
     * @return NetworkDeviceIpInterface[]
     */
    abstract public function getDeviceIps(): array;

    abstract public function addQosDevice(Device $qosDevice);

    abstract public function removeQosDevice(Device $qosDevice);

    /**
     * @return Collection|Device[]
     */
    abstract public function getQosDevices();

    abstract public function getQosAttributes(): array;

    abstract public function isSendPingNotifications(): bool;

    protected function processDeviceIp(
        NetworkDeviceIpInterface $ip,
        array &$ipsLastSuccessfully,
        array &$ipsLastNotSuccessfully
    ) {
        $ipString = long2ip($ip->getIpRange()->getIpAddress());

        if ($ip->getWasLastConnectionSuccessful() &&
            ! array_key_exists($ipString, $ipsLastSuccessfully)
        ) {
            $ipsLastSuccessfully[$ipString] = $ip;

            return;
        }

        if (! $ip->getWasLastConnectionSuccessful() &&
            ! array_key_exists($ipString, $ipsLastNotSuccessfully)
        ) {
            $ipsLastNotSuccessfully[$ipString] = $ip;
        }
    }
}
