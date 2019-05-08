<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Sync;

use AppBundle\Entity\ServiceDevice;
use AppBundle\Util\Mac;

class AirOsServiceDevice extends AirOs
{
    public function readGeneralInformation(): Device
    {
        $this->readCommandsFromDevice();

        $this->readModelName();
        $this->readMacAddress();

        return $this;
    }

    private function readMacAddress()
    {
        if (! isset($this->status->interfaces)) {
            return;
        }

        foreach ($this->status->interfaces as $interface) {
            if (in_array($interface->ifname, ServiceDevice::AIROS_MAC_ADDRESS_INTERFACE_NAME)) {
                $macAddress = Mac::format($interface->hwaddr);

                $this->device->setMacAddress($macAddress);

                break;
            }
        }
    }
}
