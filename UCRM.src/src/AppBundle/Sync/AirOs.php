<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Sync;

use AppBundle\Component\QoS\CommandLogger;
use AppBundle\Entity\BaseDevice;
use AppBundle\Entity\Device as NetworkDevice;
use AppBundle\Entity\DeviceInterface;
use AppBundle\Entity\DeviceInterfaceIp;
use AppBundle\Entity\Option;
use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceDevice;
use AppBundle\Entity\Vendor;
use AppBundle\Entity\WirelessStatisticsServiceShortTerm;
use AppBundle\Entity\WirelessStatisticsShortTerm;
use AppBundle\Service\Encryption;
use AppBundle\Service\Options;
use AppBundle\Sync\Exceptions\EmptyResponseException;
use AppBundle\Sync\Exceptions\QoSSyncNotSupportedException;
use AppBundle\Sync\Items\IpAddress;
use AppBundle\Sync\Items\NetworkInterface;
use AppBundle\Util\File;
use AppBundle\Util\Mac;
use Defuse\Crypto\Exception\CryptoException;
use Doctrine\ORM\EntityManager;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Nette\Utils\Strings;
use Symfony\Component\Translation\TranslatorInterface;

class AirOs extends UbntDevice
{
    const AAA_1_STATUS = 'aaa.1.status';
    const AAA_1_WPA_MODE = 'aaa.1.wpa.mode';
    const AAA_1_WPA_PSK = 'aaa.1.wpa.psk';
    const ENABLED = 'enabled';
    const SNMP_COMMUNITY = 'snmp.community';
    const SNMP_STATUS = 'snmp.status';
    const WEP = 'WEP';
    const WPA = 'WPA';
    const WPA_SUPPLICANT_STATUS = 'wpasupplicant.status';
    const SAVE_COMMAND = '/usr/etc/rc.d/rc.softrestart save > /dev/null 2>&1';

    const UNMANAGED_INTERFACES = [
        'lo',
        'wifi0',
        'airview1',
        'gre0',
        'sit0',
        'teql0',
        'tunl0',
        'wifi1',
    ];

    const SET_DEFAULT_NETMASK = false;

    /**
     * @var \stdClass
     */
    protected $status;

    /**
     * @var \stdClass
     */
    protected $interfaces;

    /**
     * @var \stdClass|null
     */
    protected $stations;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var array
     */
    protected $routerIps = [];

    /**
     * @var CommandLogger
     */
    private $commandLogger;

    public function __construct(
        string $rootDir,
        EntityManager $em,
        Encryption $encryption,
        TranslatorInterface $translator,
        Ssh $ssh,
        File $file,
        Options $options,
        CommandLogger $commandLogger
    ) {
        parent::__construct($rootDir, $em, $encryption, $translator, $ssh, $file, $options);
        $this->commandLogger = $commandLogger;
    }

    public function readGeneralInformation(): Device
    {
        $this->readCommandsFromDevice();

        $this->readModelName();

        $this->readSnmp();

        return $this;
    }

    public function searchForInterfaces(): Device
    {
        $routerAddressList = [];

        foreach ($this->getIps() as $ip) {
            $ipAddress = new IpAddress();
            $ipAddress->ip = long2ip($ip->ipAddress);
            $ipAddress->ipInt = $ip->ipAddress;
            $ipAddress->netmask = $ip->netmask;

            $routerAddressList[$ip->devname][$ipAddress->ipInt] = $ipAddress;
            ksort($routerAddressList[$ip->devname]);
        }

        foreach ($this->status->interfaces ?? [] as $interface) {
            if (in_array($interface->ifname, self::UNMANAGED_INTERFACES, true)) {
                continue;
            }

            $networkInterface = new NetworkInterface();
            $networkInterface->macAddress = Mac::formatView($interface->hwaddr);
            $networkInterface->internalId = $this->getInternalId($interface->ifname);
            $networkInterface->internalType = $this->getInternalInterfaceType($interface->ifname);
            $networkInterface->internalName = $interface->ifname;

            $interface->internalId = $networkInterface->internalId;
            $interface->internalName = $networkInterface->internalName;
            $interface->internalType = $networkInterface->internalType;
            $interface->macAddress = $networkInterface->macAddress;
            $guessedInterfaceType = $this->getInterfaceType($interface->internalName);

            $deviceInterface = $this->matchInterfaceByField('internalName', $networkInterface->internalName);

            if (null === $deviceInterface) {
                $deviceInterface = $this->matchInterfaceByField('internalId', $networkInterface->internalId);
            }

            $isVLAN = Strings::contains($interface->internalType, '.');
            if (null === $deviceInterface && $guessedInterfaceType !== DeviceInterface::TYPE_BRIDGE && ! $isVLAN) {
                $deviceInterface = $this->matchInterfaceByMacAddress($networkInterface->macAddress);
            }

            if (null === $deviceInterface) {
                $deviceInterface = $this->findDeviceInterfaceByIpAddress($routerAddressList, $interface->ifname);
            }

            if (null === $deviceInterface && $interface->ifname === DeviceInterface::WIRELESS_NAME) {
                $deviceInterface = $this->matchInterfaceByField('ssid', $this->status->wireless->essid);
            }

            if (null === $deviceInterface) {
                $deviceInterface = new DeviceInterface();
                $deviceInterface->setName($networkInterface->internalName)
                    ->setDevice($this->device)
                    ->setType(DeviceInterface::TYPE_UNKNOWN);

                $this->logAddedNewInterface($networkInterface->internalName);
                $this->em->persist($deviceInterface);
            }

            if ($guessedInterfaceType !== DeviceInterface::TYPE_UNKNOWN) {
                $deviceInterface->setType($guessedInterfaceType);
            }

            if (in_array($this->version, [5, 7], true) &&
                property_exists($this->interfaces, $networkInterface->internalName)
            ) {
                $interface->mtu = (int) $this->interfaces->{$networkInterface->internalName}->mtu;
            }

            $this->updateEntityAttribute(
                $deviceInterface,
                $interface,
                'internalName'
            );

            $this->updateEntityAttribute(
                $deviceInterface,
                $interface,
                'internalId'
            );

            $this->updateEntityAttribute(
                $deviceInterface,
                $interface,
                'internalType'
            );

            $this->updateEntityAttribute(
                $deviceInterface,
                $interface,
                'macAddress'
            );

            $this->updateEntityAttribute(
                $deviceInterface,
                $interface,
                'enabled'
            );

            $this->updateEntityAttribute(
                $deviceInterface,
                $interface,
                'mtu'
            );
        }

        $this->em->flush();
        $this->em->refresh($this->device);

        return $this;
    }

    public function searchForWirelessInterfaces(): Device
    {
        $deviceInterface = $this->getWirelessInterface();

        if (null !== $deviceInterface && property_exists($this->interfaces, DeviceInterface::WIRELESS_NAME)) {
            if (in_array($this->version, [5, 6, 7], true)) {
                $interface = $this->interfaces->{DeviceInterface::WIRELESS_NAME};

                $this->updateEntityAttribute(
                    $deviceInterface,
                    $interface->wireless,
                    'chwidth',
                    'channelWidth'
                );

                if (isset($this->status->wireless)) {
                    $this->updateEntityAttribute(
                        $deviceInterface,
                        $this->status->wireless,
                        'opmode',
                        'wirelessProtocol'
                    );
                }
            } elseif ($this->version === 8) {
                $interface = $this->status->wireless ?? new \stdClass();

                $this->updateEntityAttribute(
                    $deviceInterface,
                    $interface,
                    'chanbw',
                    'channelWidth'
                );
            } else {
                $interface = new \stdClass();
            }

            if (array_key_exists('wireless.1.ssid', $this->config) && in_array($this->version, [7, 8], true)) {
                $this->status->wireless->essid = $this->config['wireless.1.ssid'];
            }

            $this->updateEntityAttribute(
                $deviceInterface,
                $this->status->wireless,
                'essid',
                'ssid'
            );

            $interface->wirelessMode = DeviceInterface::MODE_UNKNOWN;

            switch ($this->status->wireless->mode) {
                case DeviceInterface::AP:
                    if (! isset($this->config['wireless.1.wds.1.peer']) &&
                        ! isset($this->config['wireless.1.wds.2.peer']) &&
                        ! isset($this->config['wireless.1.wds.3.peer']) &&
                        ! isset($this->config['wireless.1.wds.4.peer']) &&
                        ! isset($this->config['wireless.1.wds.5.peer']) &&
                        ! isset($this->config['wireless.1.wds.6.peer'])
                    ) {
                        $interface->wirelessMode = DeviceInterface::MODE_AP;
                    } else {
                        $interface->wirelessMode = DeviceInterface::MODE_ACCESS_POINT_REPEATER;
                    }
                    break;
                case DeviceInterface::STA:
                    $interface->wirelessMode = DeviceInterface::MODE_STATION;
                    break;
                case DeviceInterface::AP_PTP:
                    $interface->wirelessMode = DeviceInterface::MODE_ACCESS_POINT_PTP;
                    break;
                case DeviceInterface::AP_PTMP:
                    if (property_exists($this->status->wireless, 'compat_11n') &&
                        $this->status->wireless->compat_11n === 0
                    ) {
                        $interface->wirelessMode = DeviceInterface::MODE_ACCESS_POINT_PTMP_AIRMAX_AC;
                    } else {
                        $interface->wirelessMode = DeviceInterface::MODE_ACCESS_POINT_PTMP_AIRMAX_MIXED;
                    }
                    break;
                case DeviceInterface::STA_PTP:
                    $interface->wirelessMode = DeviceInterface::MODE_STATION_PTP;
                    break;
                case DeviceInterface::STA_PTMP:
                    $interface->wirelessMode = DeviceInterface::MODE_STATION_PTMP;
                    break;
            }

            $this->updateEntityAttribute(
                $deviceInterface,
                $interface,
                'wirelessMode',
                'mode'
            );

            $interface->frequency = $this->status->wireless->frequency;

            $this->updateEntityAttribute(
                $deviceInterface,
                $interface,
                'frequency'
            );

            $this->em->persist($deviceInterface);
        }

        return $this;
    }

    public function searchForWirelessSecurity(): Device
    {
        $deviceInterface = $this->getWirelessInterface();
        if (null !== $deviceInterface && property_exists($this->interfaces, DeviceInterface::WIRELESS_NAME)) {
            if (in_array($this->version, [5, 6, 7], true)) {
                $interface = $this->interfaces->{DeviceInterface::WIRELESS_NAME};
            } elseif ($this->version === 8) {
                $interface = $this->status->wireless;
            } else {
                $interface = new \stdClass();
            }

            if ((
                    array_key_exists(self::AAA_1_STATUS, $this->config) &&
                    $this->config[self::AAA_1_STATUS] === self::ENABLED
                ) || (
                    array_key_exists(self::WPA_SUPPLICANT_STATUS, $this->config) &&
                    $this->config[self::WPA_SUPPLICANT_STATUS] === self::ENABLED
                )
            ) {
                $interface->encryptionMode = DeviceInterface::ENCRYPTION_MODE_DYNAMIC_KEYS;
                $interface->groupCiphers = DeviceInterface::CIPHER_AES;
                $interface->unicastCiphers = DeviceInterface::CIPHER_AES;
                if ((
                        in_array($this->version, [6, 7, 8], true)
                        && array_key_exists(self::AAA_1_WPA_MODE, $this->config)
                        && $this->config[self::AAA_1_WPA_MODE] === '1'
                    ) || (
                        $this->version === 5 &&
                        $interface->wireless->security === self::WPA
                    )
                ) {
                    $interface->encryptionType = DeviceInterface::ENCRYPTION_TYPE_WPAPSK;
                } else {
                    $interface->encryptionType = DeviceInterface::ENCRYPTION_TYPE_WPA2PSK;
                }
            } elseif (in_array($this->version, [5, 6], true) && $interface->wireless->security === self::WEP) {
                $interface->encryptionMode = DeviceInterface::ENCRYPTION_MODE_STATIC_KEYS_REQUIRED;
                $interface->encryptionType = DeviceInterface::ENCRYPTION_TYPE_WEP;
                $interface->groupCiphers = DeviceInterface::CIPHER_NONE;
                $interface->unicastCiphers = DeviceInterface::CIPHER_NONE;
            } else {
                $interface->encryptionMode = DeviceInterface::ENCRYPTION_MODE_NONE;
                $interface->encryptionType = DeviceInterface::ENCRYPTION_TYPE_NONE;
                $interface->groupCiphers = DeviceInterface::CIPHER_NONE;
                $interface->unicastCiphers = DeviceInterface::CIPHER_NONE;
            }

            $this->updateEntityAttribute(
                $deviceInterface,
                $interface,
                'encryptionType'
            );

            $this->updateEntityAttribute(
                $deviceInterface,
                $interface,
                'encryptionMode'
            );

            $this->updateEntityAttribute(
                $deviceInterface,
                $interface,
                'groupCiphers'
            );

            $this->updateEntityAttribute(
                $deviceInterface,
                $interface,
                'unicastCiphers'
            );

            $interface->encryptionKeyWpa = $this->config[self::AAA_1_WPA_PSK] ?? null;
            $interface->encryptionKeyWpa2 = $this->config[self::AAA_1_WPA_PSK] ?? null;

            $this->forceDecrypt($deviceInterface, 'encryptionKeyWpa');
            $this->forceDecrypt($deviceInterface, 'encryptionKeyWpa2');

            $this->updateEntityAttribute(
                $deviceInterface,
                $interface,
                'encryptionKeyWpa'
            );

            $this->updateEntityAttribute(
                $deviceInterface,
                $interface,
                'encryptionKeyWpa2'
            );

            $this->em->persist($deviceInterface);
        }

        return $this;
    }

    /**
     * Force-decrypts field if it is encrypted.
     * Some fields were previously encrypted but only here, not in the form. There is no need to encrypt them.
     */
    private function forceDecrypt(DeviceInterface $entity, string $field)
    {
        $getter = sprintf('get%s', ucwords($field));
        $setter = sprintf('set%s', ucwords($field));

        try {
            $value = $this->encryption->decrypt($entity->$getter());
        } catch (CryptoException $e) {
            // It was not encrypted.
            return;
        }

        $entity->$setter($value);
    }

    public function searchForInterfaceIpsChangedInterface(): Device
    {
        $ips = $this->getIps();

        foreach ($ips as $ip) {
            $this->changeIpOnInterface($ip->ipAddress, $ip->netmask, $ip->internalId, $ip->devname);
        }

        return $this;
    }

    public function searchForInterfaceIps(): Device
    {
        $activeIpInternalId = [];
        $ipsForRemoval = [];

        foreach ($this->getIps() as $ip) {
            $activeIpInternalId[] = $ip->internalId;
            $deviceInterface = $this->matchInterfaceByField('internalName', $ip->devname);

            if (! $deviceInterface) {
                continue;
            }

            $existingIps = $deviceInterface->getInterfaceIps();

            /** @var DeviceInterfaceIp $deviceInterfaceIp */
            $deviceInterfaceIp = $this->deviceInterfaceIpRepository->findOneBy(
                [
                    'ipRange.ipAddress' => $ip->ipAddress,
                    'interface' => $deviceInterface,
                ]
            );

            if (null === $deviceInterfaceIp) {
                $deviceInterfaceIp = new DeviceInterfaceIp();
                $deviceInterfaceIp->setInterface($deviceInterface)
                    ->setInternalId($ip->internalId)
                    ->setNatPublicIp(null);

                $deviceInterfaceIp
                    ->getIpRange()
                    ->setCidr($ip->ipAddress, $ip->netmask);

                $this->setInterfaceIpAccessible($deviceInterfaceIp);

                if (long2ip($ip->ipAddress) === $this->ip) {
                    $deviceInterfaceIp->setWasLastConnectionSuccessful(true);
                }

                $this->em->persist($deviceInterfaceIp);

                $this->logAddedNewIp($deviceInterface, $deviceInterfaceIp);
            } else {
                $ipRange = $deviceInterfaceIp->getIpRange();
                $from = $ipRange->getRangeForView();
                $ipRange->setCidr($ip->ipAddress, $ip->netmask);
                if ($ipRange->getRangeForView() !== $from) {
                    $this->logChangedAttribute($deviceInterfaceIp, 'ipRange', $from, $ipRange->getRangeForView());
                }
            }

            foreach ($existingIps as $existingIp) {
                if ($deviceInterfaceIp !== $existingIp) {
                    $ipsForRemoval[] = $existingIp;
                }
            }
        }

        $ipsForRemoval = array_merge(
            $ipsForRemoval,
            $this->deviceInterfaceIpRepository->getIpsForRemovalByInternalId($this->device, $activeIpInternalId)
        );
        $removedIps = $this->deviceInterfaceIpRepository->removeIps($ipsForRemoval);
        $this->logRemovedIps($removedIps);

        return $this;
    }

    public function searchForUnknownConnectedDevices(): Device
    {
        if (
            null === $this->stations
            || (
                isset($this->status->wireless->mode)
                &&
                $this->status->wireless->mode === DeviceInterface::STA_PTMP
            )
        ) {
            return $this;
        }

        $deviceInterface = $this->deviceInterfaceRepository->findOneBy(
            [
                'internalName' => DeviceInterface::WIRELESS_NAME,
                'device' => $this->device,
            ]
        );

        if (! $deviceInterface) {
            return $this;
        }

        foreach ($this->stations->sta ?? [] as $station) {
            $macAddress = Mac::format($station->mac);
            $lastIp = null;
            if (property_exists($station, 'lastip')) {
                $lastIp = $station->lastip ? ip2long($station->lastip) : null;
            }

            list($isKnown, $unknownServiceDevice) = $this->findUnknownDevice($macAddress, $lastIp);
            if ($isKnown) {
                continue;
            }

            if (null === $unknownServiceDevice) {
                $unknownServiceDevice = new ServiceDevice();
                $unknownServiceDevice->setMacAddress($macAddress)
                    ->setFirstSeen(new \DateTime());
            }

            $unknownServiceDevice->setInterface($deviceInterface)
                ->setLastSeen(new \DateTime())
                ->setRxRate($station->rx)
                ->setTxRate($station->tx)
                ->setUptime($station->uptime)
                ->setSignalStrength($station->signal);

            if (null !== $lastIp) {
                $unknownServiceDevice->setLastIp(long2ip($lastIp));
            }

            if (property_exists($station, 'ccq')) {
                $unknownServiceDevice->setTxCcq($station->ccq);
            }

            $this->em->persist($unknownServiceDevice);
        }

        return $this;
    }

    public function saveStatistics(string $timestamp = null): Device
    {
        if (! $this->device->getCreateSignalStatistics()) {
            return $this;
        }

        $signal = -100;
        $remoteSignal = -100;
        $ccq = 0;
        $rxRate = 0;
        $txRate = 0;

        if ($this->version === 8) {
            foreach ($this->status->wireless->sta ?? [] as $sta) {
                $signal = $sta->signal > $signal ? $sta->signal : $signal;
                $remoteSignal = $sta->remote->signal > $remoteSignal ? $sta->remote->signal : $remoteSignal;
                if ($sta->airmax ?? false) {
                    $rxRate = $sta->airmax->uplink_capacity > $rxRate ? $sta->airmax->uplink_capacity : $rxRate;
                    $txRate = $sta->airmax->downlink_capacity > $txRate ? $sta->airmax->downlink_capacity : $txRate;
                }
            }

            $rxRate /= 1024;
            $txRate /= 1024;
        } else {
            if (! isset($this->status->wireless)) {
                return $this;
            }

            $ccq = isset($this->status->wireless->ccq) ? $this->status->wireless->ccq / 10 : null;
            $rxRate = isset($this->status->wireless->rxrate) ? intval($this->status->wireless->rxrate) : null;
            $txRate = isset($this->status->wireless->txrate) ? intval($this->status->wireless->txrate) : null;
            $signal = $this->status->wireless->signal ?? null;
        }

        if ($this->device instanceof NetworkDevice) {
            $wirelessStatisticsShortTerm = new WirelessStatisticsShortTerm();
            $wirelessStatisticsShortTerm->setDevice($this->device);
        } else {
            $wirelessStatisticsShortTerm = new WirelessStatisticsServiceShortTerm();
            $wirelessStatisticsShortTerm->setServiceDevice($this->device);
        }

        $wirelessStatisticsShortTerm
            ->setCcq($ccq)
            ->setRxRate($rxRate)
            ->setTxRate($txRate)
            ->setSignal($signal)
            ->setRemoteSignal($remoteSignal)
            ->setTime(new \DateTime($timestamp));

        $this->em->persist($wirelessStatisticsShortTerm);

        return $this;
    }

    /**
     * @throws QoSSyncNotSupportedException
     */
    public function syncQos()
    {
        if (! $this->device instanceof ServiceDevice) {
            throw new QoSSyncNotSupportedException('AirOS', $this->device, 'service device');
        }

        $service = $this->device->getService();
        if ($this->options->get(Option::QOS_ENABLED)
            && $this->options->get(Option::QOS_DESTINATION) === Option::QOS_DESTINATION_CUSTOM
            && $this->device->getQosEnabled() === BaseDevice::QOS_THIS
            && $service->getStatus() !== Service::STATUS_ENDED
            && ! $service->isDeleted()
            && $service->getTariff()->getDownloadSpeed() !== null
            && $service->getTariff()->getUploadSpeed() !== null
        ) {
            $this->enableQos();
        } else {
            $this->disableQos();
        }
    }

    private function disableQos()
    {
        $commands = [
            'removePrevShaper' => [
                'command' => 'sed -i "/^tshaper/d" /tmp/system.cfg',
                'message' => 'Invalid removing previous shaper.',
            ],
            'save' => [
                'command' => self::SAVE_COMMAND,
                'message' => 'Invalid save response',
            ],
        ];

        $this->executeQosCommands($commands);
    }

    private function enableQos()
    {
        /** @var Service $service */
        $service = $this->device->getService();
        $servicePlan = $service->getTariff();

        if ($this->options->get(Option::QOS_INTERFACE_AIR_OS) === Option::QOS_INTERFACE_AIR_OS_WLAN) {
            $config = [
                'tshaper.status=enabled',
                'tshaper.1.status=enabled',
                'tshaper.1.input.status=enabled',
                'tshaper.1.input.rate=%download%',
                'tshaper.1.input.burst=%download_burst%',
                'tshaper.1.output.status=enabled',
                'tshaper.1.output.rate=%upload%',
                'tshaper.1.output.burst=%upload_burst%',
                'tshaper.1.devname=ath0',
            ];
        } else {
            $config = [
                'tshaper.status=enabled',
                'tshaper.1.status=enabled',
                'tshaper.1.input.status=disabled',
                'tshaper.1.input.burst=0',
                'tshaper.1.output.status=enabled',
                'tshaper.1.output.rate=%upload%',
                'tshaper.1.output.burst=%upload_burst%',
                'tshaper.1.devname=ath0',
                'tshaper.2.status=enabled',
                'tshaper.2.input.status=disabled',
                'tshaper.2.input.burst=0',
                'tshaper.2.output.status=enabled',
                'tshaper.2.output.rate=%download%',
                'tshaper.2.output.burst=%download_burst%',
                'tshaper.2.devname=eth0',
            ];
        }

        $config = strtr(
            implode(PHP_EOL, $config),
            [
                '%download%' => round($servicePlan->getDownloadSpeed() * 1024),
                '%upload%' => round($servicePlan->getUploadSpeed() * 1024),
                '%download_burst%' => $this->getBurstByVersion(
                    $servicePlan->getDownloadBurst(),
                    $servicePlan->getDownloadSpeed()
                ),
                '%upload_burst%' => $this->getBurstByVersion(
                    $servicePlan->getUploadBurst(),
                    $servicePlan->getUploadSpeed()
                ),
            ]
        );

        $commands = [
            'removePrevShaper' => [
                'command' => 'sed -i "/^tshaper/d" /tmp/system.cfg',
                'message' => 'Invalid removing previous shaper.',
            ],
            'removeEmptyLines' => [
                'command' => 'sed -i "/^$/d" /tmp/system.cfg',
                'message' => 'Invalid removing empty lines.',
            ],
            'speed' => [
                'command' => sprintf(
                    'printf "%s" >> /tmp/system.cfg',
                    PHP_EOL . $config . PHP_EOL
                ),
                'message' => 'Invalid speed response',
            ],
            'save' => [
                'command' => self::SAVE_COMMAND,
                'message' => 'Invalid save response',
            ],
        ];

        $this->executeQosCommands($commands);
    }

    /**
     * @return string
     */
    private function executeQosCommands(array $commands)
    {
        $info = [
            'ip' => $this->ip,
            'os' => $this->device->getOsVersion() ?? Vendor::TYPES[Vendor::AIR_OS],
            'device' => sprintf('Service device %d', $this->device->getId()),
        ];

        $loggableCommands = array_map(
            function (array $command) {
                return $command['command'] ?? null;
            },
            $commands
        );

        $this->commandLogger->logCommand(implode(PHP_EOL, array_filter($loggableCommands)), $info);
        $output = $this->callCommands($commands);
        $this->commandLogger->logOutput($output, $info);

        return $output;
    }

    /**
     * @param int|string|bool $from
     * @param int|string|bool $to
     * @param \stdClass       $attributes
     */
    protected function formatLogValues(string $attributeName, $from, $to, $attributes): array
    {
        list($from, $to) = parent::formatLogValues($attributeName, $from, $to, $attributes);

        switch ($attributeName) {
            case 'ipAddress':
                $from = sprintf('%s/%d', long2ip($from), $attributes->netmask);
                $to = sprintf('%s/%d', long2ip($to), $attributes->netmask);
                break;
            case 'netmask':
                $from = sprintf('%s/%d', long2ip($attributes->ipAddress), $from);
                $to = sprintf('%s/%d', long2ip($attributes->ipAddress), $to);
                break;
        }

        return [$from, $to];
    }

    protected function readModelName()
    {
        if (isset($this->status->host) && property_exists($this->status->host, 'devmodel')) {
            $this->device->setModelName(trim($this->status->host->devmodel));
        }
    }

    protected function readCommandsFromDevice()
    {
        $commands = [
            'version' => [
                'command' => 'cat /etc/version',
                'message' => 'Invalid version response.',
            ],
            'status' => [
                'command' => '/usr/www/status.cgi',
                'message' => 'Invalid status response.',
            ],
            'interfaces' => [
                'command' => '/usr/www/iflist.cgi',
                'message' => 'Invalid interface response.',
            ],
            'stations' => [
                'command' => '/usr/www/sta.cgi',
                'message' => 'Invalid station response.',
            ],
            'config' => [
                'command' => 'cat /tmp/system.cfg',
                'message' => 'Invalid config response.',
            ],
            'backup' => [
                'command' => '/usr/www/cfg.cgi',
                'message' => 'Invalid backup response.',
            ],
        ];

        $responseRaw = $this->callCommands($commands);

        foreach ($commands as $property => $command) {
            $start = strpos($responseRaw, $command['startDelimiter']) + strlen($command['startDelimiter']);
            $end = strpos($responseRaw, $command['endDelimiter']);

            $response = trim(substr($responseRaw, $start, ($end - $start)));
            $type = strpos($response, 'text/html') ? 'html' : 'json';
            $response = preg_replace('~^\s+~imus', '', trim($response));

            if ($property === 'version') {
                $this->processVersionResponse($response);
            } elseif ($property === 'status') {
                $this->status = $this->processCgiResponse($response, $command['message'], $type);
            } elseif ($property === 'interfaces') {
                $this->processInterfaceResponse($response, $command['message'], $type);
            } elseif ($property === 'stations') {
                try {
                    $this->stations = $this->processCgiResponse($response, $command['message'], $type);
                } catch (EmptyResponseException $e) {
                    $this->stations = null;
                }
            } elseif ($property === 'config') {
                $this->processConfigResponse($response);
            } elseif ($property === 'backup') {
                $this->saveBackup($response);
            }
        }
    }

    private function getIps(): array
    {
        if (! empty($this->routerIps)) {
            return $this->routerIps;
        }

        $this->routerIps = [];
        $ips = [];

        for ($i = 1; $i <= 99; ++$i) {
            $ipKey = sprintf('netconf.%d.ip', $i);
            $netmaskKey = sprintf('netconf.%d.netmask', $i);
            $devnameKey = sprintf('netconf.%d.devname', $i);

            if (array_key_exists($ipKey, $this->config) && $this->config[$ipKey] !== '0.0.0.0' &&
                array_key_exists($netmaskKey, $this->config) && null !== $this->config[$netmaskKey]
            ) {
                $ip = new \stdClass();
                $ip->ipAddress = ip2long($this->config[$ipKey]);
                $ip->netmask = $this->maskToCidr($this->config[$netmaskKey]);
                $ip->devname = $this->config[$devnameKey];
                $ip->internalId = $i;
                $ips[$ip->devname] = $ip;
            }
        }

        ksort($ips);

        $this->routerIps = $ips;

        return $this->routerIps;
    }

    /**
     * @return DeviceInterface|null
     */
    private function getWirelessInterface()
    {
        $internalType = $this->getInternalInterfaceType(DeviceInterface::WIRELESS_NAME);

        return $this->deviceInterfaceRepository->findOneBy(
            [
                'internalName' => DeviceInterface::WIRELESS_NAME,
                'internalType' => $internalType,
                'device' => $this->device,
            ]
        );
    }

    private function processInterfaceResponse(string $response, string $message, string $type)
    {
        $this->interfaces = new \stdClass();

        $interfaces = $this->processCgiResponse($response, $message, $type);

        if ($this->version === 8 && empty(current($interfaces))) {
            $interfaces = [$this->status->interfaces];
        }

        $interfacesSorted = [];

        if (empty(current($interfaces))) {
            return;
        }

        foreach (current($interfaces) as $interface) {
            $interfacesSorted[$interface->ifname] = $interface;
        }

        ksort($interfacesSorted);

        foreach ($interfacesSorted as $interface) {
            $this->interfaces->{$interface->ifname} = new \stdClass();
            $this->interfaces->{$interface->ifname} = $interface;
        }
    }

    private function processConfigResponse(string $response)
    {
        foreach (explode(PHP_EOL, $response) as $line) {
            if (strpos($line, '=') === false) {
                continue;
            }

            list($key, $value) = explode('=', $line, 2);
            $this->config[$key] = $value ? $value : null;
        }
    }

    private function saveBackup(string $response)
    {
        preg_match('~filename=([A-Z0-9\-]+)\.cfg~imus', $response, $suffix);
        $response = trim(preg_replace('~^(Content.*)$~im', '', $response));
        $backupHash = md5($response);

        if ($backupHash === $this->device->getBackupHash()) {
            return;
        }

        $directory = $this->file->getDeviceBackupDirectory($this->device);

        $filename = sprintf(
            '%d%s.cfg',
            time(),
            empty($suffix) ? '' : sprintf('-%s', $suffix[1])
        );

        $this->file->save($directory, $filename, $response);

        $this->device->setBackupHash($backupHash);
        $this->device->setLastBackupTimestamp(new \DateTime());
    }

    /**
     * @throws EmptyResponseException
     */
    private function processCgiResponse(string $responseRaw, string $message, string $type): \stdClass
    {
        $responseArray = explode($type, $responseRaw);
        if (array_key_exists(1, $responseArray)) {
            try {
                $return = Json::decode(trim($responseArray[1]));
            } catch (JsonException $e) {
                $return = null;
            }

            if (empty($return)) {
                throw new EmptyResponseException($message);
            }

            if (is_array($return)) {
                $returnObject = new \stdClass();
                $returnObject->sta = $return;
                $return = $returnObject;
            }

            return $return;
        }

        return new \stdClass();
    }

    private function readSnmp()
    {
        if ($this->device instanceof ServiceDevice) {
            return;
        }

        if (array_key_exists(self::SNMP_COMMUNITY, $this->config) &&
            (
                (
                    array_key_exists(self::SNMP_STATUS, $this->config) &&
                    $this->config[self::SNMP_STATUS] === self::ENABLED
                ) ||
                ! array_key_exists(self::SNMP_STATUS, $this->config)
            )
        ) {
            $this->device->setSnmpCommunity($this->config[self::SNMP_COMMUNITY]);
        } else {
            $this->device->setSnmpCommunity(null);
        }
    }

    public function maskToCidr(string $netmask): int
    {
        $long = ip2long($netmask);
        $base = ip2long('255.255.255.255');

        return 32 - log(($long ^ $base) + 1, 2);
    }

    private function getBurstByVersion(?int $burst, int $speed): int
    {
        if (! $this->version) {
            $this->readCommandsFromDevice();
        }

        if ($burst === null && version_compare('8', $this->version, '>=')) {
            $burst = (int) round($speed * 1024 / 100 + 20);
        }

        return (int) $burst;
    }
}
