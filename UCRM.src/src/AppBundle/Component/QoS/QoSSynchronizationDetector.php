<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\QoS;

use AppBundle\Entity\Option;
use AppBundle\Entity\ServiceDevice;

class QoSSynchronizationDetector
{
    public const UNSYNC_GATEWAYS = 1;
    public const UNSYNC_PARENTS = 2;

    public function getSynchronizationType(ServiceDevice $device, string $qosDestination): ?int
    {
        if (null !== $device->getManagementIpAddress() || ! $device->getServiceIps()->isEmpty()) {
            switch ($qosDestination) {
                case Option::QOS_DESTINATION_GATEWAY:
                    return $device->getQosServiceIps() ? self::UNSYNC_GATEWAYS : null;
                case Option::QOS_DESTINATION_CUSTOM:
                    return self::UNSYNC_PARENTS;
            }
        }

        return null;
    }
}
