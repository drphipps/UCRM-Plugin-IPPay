<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Sync;

use AppBundle\Entity\DeviceInterface;
use AppBundle\Entity\WirelessStatisticsServiceShortTerm;
use AppBundle\Util\Mac;

class RouterOsServiceDevice extends RouterOs
{
    public function readGeneralInformation(): Device
    {
        $this->readCommandsFromDevice();

        $this->readModelName();
        $this->readOsVersion();
        $this->readMacAddress();

        $this->saveConfiguration();

        return $this;
    }

    public function saveStatistics(): Device
    {
        $attributes = [
            'rx-rate',
            'tx-rate',
            'signal-strength',
            'tx-ccq',
        ];

        $signal = -100;
        $ccq = 0;
        $rxRate = 0;
        $txRate = 0;

        $registeredDevices = $this->getRawSectionList(self::SECTION_INTERFACE_WIRELESS_REGISTRATION_TABLE, $attributes);

        if (! empty($registeredDevices)) {
            $device = current($registeredDevices);
            $signal = intval($device['signal-strength']) > $signal ? intval($device['signal-strength']) : $signal;
            $rxRate = intval($device['rx-rate']) > $rxRate ? intval($device['rx-rate']) : $rxRate;
            $txRate = intval($device['tx-rate']) > $txRate ? intval($device['tx-rate']) : $txRate;
            $ccq = intval($device['tx-ccq'] ?? 0) > $ccq ? intval($device['tx-ccq'] ?? 0) : $ccq;
        }

        $wirelessStatisticsShortTerm = (new WirelessStatisticsServiceShortTerm())
            ->setServiceDevice($this->device)
            ->setCcq($ccq)
            ->setRxRate($rxRate)
            ->setTxRate($txRate)
            ->setSignal($signal)
            ->setTime(new \DateTime());

        $this->em->persist($wirelessStatisticsShortTerm);

        return $this;
    }

    private function readMacAddress()
    {
        $attributes = ['type', 'mac-address'];
        $interfaces = $this->getRawSectionList(self::SECTION_INTERFACE, $attributes);
        foreach ($interfaces as $interface) {
            if ($interface['type'] === DeviceInterface::WLAN) {
                $macAddress = Mac::format($interface['mac-address']);

                $this->device->setMacAddress($macAddress);

                break;
            }
        }
    }
}
