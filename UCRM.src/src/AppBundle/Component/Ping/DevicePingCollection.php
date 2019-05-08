<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Ping;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

class DevicePingCollection extends ArrayCollection
{
    /**
     * @return DevicePing|null
     */
    public function find(DevicePing $needle)
    {
        $found = $this->filter(
            function (DevicePing $item) use ($needle) {
                return $item->getDeviceId() === $needle->getDeviceId()
                    && $item->getType() === $needle->getType()
                    && $item->getIpAddress() === $needle->getIpAddress();
            }
        );

        return $found->isEmpty() ? null : $found->first();
    }

    /**
     * @return DevicePing|null
     */
    public function findByDeviceId(int $deviceId, int $type)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('deviceId', $deviceId))
            ->andWhere(Criteria::expr()->eq('type', $type));
        $found = $this->matching($criteria);

        return $found->isEmpty() ? null : $found->first();
    }

    /**
     * @return DevicePing|null
     */
    public function findByIpAddress(string $ipAddress)
    {
        $criteria = Criteria::create()->where(Criteria::expr()->eq('ipAddress', $ipAddress));
        $found = $this->matching($criteria);

        return $found->isEmpty() ? null : $found->first();
    }

    public function getIpAddresses(): array
    {
        return $this->map(
            function (DevicePing $device) {
                return $device->getIpAddress();
            }
        )->toArray();
    }
}
