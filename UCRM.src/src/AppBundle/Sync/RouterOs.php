<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Sync;

use AppBundle\Entity\DeviceInterface;
use AppBundle\Entity\DeviceInterfaceIp;
use AppBundle\Entity\DeviceLog;
use AppBundle\Entity\ServiceDevice;
use AppBundle\Entity\WirelessStatisticsShortTerm;
use AppBundle\Sync\Exceptions\LoginException;
use AppBundle\Sync\Items\IpAddress;
use AppBundle\Util\Mac;
use Doctrine\Common\Collections\ArrayCollection;
use Nette\Utils\Strings;

class RouterOs extends Device
{
    const COMMENT_SIGNATURE = 'ucrm_';
    const SECTION_SYSTEM_BACKUP = '/system/backup/save';
    const SECTION_INTERFACE = '/interface';
    const SECTION_INTERFACE_WIRELESS = '/interface/wireless';
    const SECTION_INTERFACE_WIRELESS_REGISTRATION_TABLE = '/interface/wireless/registration-table';
    const SECTION_INTERFACE_WIRELESS_SECURITY_PROFILES = '/interface/wireless/security-profiles';
    const SECTION_IP_ADDRESS = '/ip/address';
    const SECTION_IP_FIREWALL_ADDRESS_LIST = '/ip/firewall/address-list';
    const SECTION_SNMP = '/snmp';
    const SECTION_SNMP_COMMUNITY = '/snmp/community';
    const SECTION_SYSTEM_RESOURCE = '/system/resource';

    const BACKUP_FILENAME = 'ucrm';

    const SET_DEFAULT_NETMASK = true;

    private const SKIP_MAC_MATCHING_FOR_TYPES = [
        DeviceInterface::BRIDGE,
        DeviceInterface::VLAN,
    ];

    /**
     * @var array
     */
    protected $resource;

    /**
     * @var array
     */
    protected $routerIps;

    /**
     * @var array
     */
    protected $wirelessInterfaces = [];

    /**
     * @var array
     */
    private static $natDstJumpRules = [
        'first_dstnat',
        'general_dstnat',
        'last_dstnat',
    ];

    /**
     * @var array
     */
    private static $natSrcJumpRules = [
        'first_srcnat',
        'general_srcnat',
        'range_srcnat',
        'last_srcnat',
    ];

    /**
     * @var RouterOsApi
     */
    private $api;

    public function connect($api = null): Device
    {
        if ($this->connected) {
            return $this;
        }

        $this->api = $api !== null ? $api : new RouterOsApi();

        $this->connected = $this->api->connect($this->ip, $this->username, $this->encryption->decrypt($this->password));

        return $this;
    }

    public function close()
    {
        $this->api->disconnect();
    }

    public function getSectionList(string $section, array $attributes): array
    {
        $data = $this->getRawSectionList($section, $attributes);

        $filtered = [];
        foreach ($data as $row) {
            if (array_key_exists('comment', $row) && Strings::startsWith($row['comment'], self::COMMENT_SIGNATURE)) {
                $row['comment'] = substr($row['comment'], strlen(self::COMMENT_SIGNATURE));
                $filtered[] = $row;
            } elseif (array_key_exists('name', $row) && Strings::startsWith($row['name'], self::COMMENT_SIGNATURE)) {
                //some sections doesn't have comment attribute, ucrm uses name attribute instead
                $filtered[] = $row;
            }
        }

        return $filtered;
    }

    public function getRawSectionList(string $section, array $attributes = [], bool $includePrint = true): array
    {
        $result = $this->api->comm(
            $includePrint ? sprintf('%s/print', $section) : $section,
            empty($attributes) ? [] : ['.proplist' => sprintf('.id,%s', implode(',', $attributes))]
        );

        return is_array($result) ? \AppBundle\Util\Strings::fixEncodingRecursive($result) : [];
    }

    public function syncFilterRules(string $serverIp): Device
    {
        $this->connect();

        $rules = [
            [
                'chain' => 'input',
                'src-address' => $serverIp,
                'comment' => 'accept_input',
                'action' => 'accept',
                'dst-port' => '',
                'protocol' => '',
                'src-address-list' => '',
                'dst-address' => '',
            ],
            [
                'chain' => 'forward',
                'src-address' => $serverIp,
                'comment' => 'accept_forward',
                'action' => 'accept',
                'dst-port' => '',
                'protocol' => '',
                'src-address-list' => '',
                'dst-address' => '',
            ],

            [
                'chain' => 'forward',
                'comment' => 'forward_first',
                'jump-target' => 'ucrm_forward_first',
                'action' => 'jump',
                'dst-port' => '',
                'protocol' => '',
                'src-address-list' => '',
                'dst-address' => '',
            ],

            [
                'chain' => 'forward',
                'comment' => 'forward_general',
                'jump-target' => 'ucrm_forward_general',
                'action' => 'jump',
                'dst-port' => '',
                'protocol' => '',
                'src-address-list' => '',
                'dst-address' => '',
            ],

            [
                'chain' => 'forward',
                'comment' => 'forward_drop',
                'jump-target' => 'ucrm_forward_drop',
                'action' => 'jump',
                'dst-port' => '',
                'protocol' => '',
                'src-address-list' => '',
                'dst-address' => '',
            ],
            [
                'chain' => 'ucrm_forward_general',
                'comment' => 'blocked_users_allow_dns',
                'protocol' => 'udp',
                'dst-port' => 53,
                'src-address-list' => self::BLOCKED_USERS,
                'action' => 'accept',
                'src-address' => '',
                'dst-address' => '',
            ],
            [
                'chain' => 'ucrm_forward_drop',
                'comment' => 'blocked_users_drop',
                'src-address-list' => self::BLOCKED_USERS,
                'dst-address' => '!' . $serverIp,
                'action' => 'drop',
                'src-address' => '',
                'dst-port' => '',
                'protocol' => '',
            ],
        ];

        $this->synchronizeSection(
            '/ip/firewall/filter',
            $rules,
            ['chain', 'comment', 'src-address-list', 'dst-address', 'action', 'src-address', 'dst-port', 'protocol'],
            true
        );

        return $this;
    }

    public function synchronizeSection(string $section, array $content, array $attrs, bool $useComments)
    {
        $remoteSectionList = $this->getSectionList($section, $attrs);
        $remoteList = $this->createIndex($remoteSectionList, $attrs);
        $localList = $this->createIndex($content, $attrs);

        $toRemove = array_diff_key($remoteList, $localList);
        $toAdd = array_diff_key($localList, $remoteList);

        $this->removeFromSection($section, array_column($toRemove, '.id'));
        $this->addToSection($section, $toAdd, $useComments);
    }

    private function synchronizeIpFirewallAddressListSection(array $content)
    {
        $section = self::SECTION_IP_FIREWALL_ADDRESS_LIST;
        $attributes = ['list', 'address', 'comment'];

        $localList = $this->createIndex($content, $attributes);
        $remoteSectionList = $this->getSectionList($section, $attributes);
        foreach ($remoteSectionList as $key => $row) {
            if (! array_key_exists('address', $row)) {
                continue;
            }

            if (! Strings::match($row['address'], '~\/[0-9]+$~')) {
                $remoteSectionList[$key]['address'] = $row['address'] . '/32';
            }
        }
        $remoteList = $this->createIndex($remoteSectionList, $attributes);
        $toRemove = array_diff_key($remoteList, $localList);
        $toAdd = array_diff_key($localList, $remoteList);

        $localListKeys = [];
        foreach ($content as $row) {
            $localKey = $this->createIndexKey($row, $attributes);
            unset($row['comment']);
            $uncommentedLocalKey = $this->createIndexKey($row, $attributes);

            $localListKeys[$uncommentedLocalKey] = $localKey;
        }

        $rawRemoteSectionList = $this->getRawSectionList($section, $attributes);
        foreach ($rawRemoteSectionList as $row) {
            unset($row['comment']);
            $remoteListKey = $this->createIndexKey($row, $attributes);

            if (array_key_exists($remoteListKey, $localListKeys)) {
                $addKey = $localListKeys[$remoteListKey];
                if (! array_key_exists($addKey, $toAdd)) {
                    continue;
                }

                $this->log(
                    sprintf(
                        'Suspension of IP "%s" skipped, because it already exists in the "%s" list.',
                        $toAdd[$addKey]['address'],
                        self::BLOCKED_USERS
                    ),
                    DeviceLog::STATUS_WARNING
                );
                unset($toAdd[$addKey]);
            }
        }

        $this->removeFromSection($section, array_column($toRemove, '.id'));
        $this->addToSection($section, $toAdd, true);
    }

    public function syncNatRules(string $serverIp, int $serverSuspendPort): Device
    {
        $this->connect();

        $rules = [];

        //add jump rules
        foreach (self::$natDstJumpRules as $jumpRule) {
            $rules[] = [
                'chain' => 'dstnat',
                'action' => 'jump',
                'comment' => $jumpRule,
                'jump-target' => self::COMMENT_SIGNATURE . $jumpRule,
                'out-interface' => '',
            ];
        }
        foreach (self::$natSrcJumpRules as $jumpRule) {
            $rules[] = [
                'chain' => 'srcnat',
                'action' => 'jump',
                'comment' => $jumpRule,
                'jump-target' => self::COMMENT_SIGNATURE . $jumpRule,
                'out-interface' => '',
            ];
        }

        $rules[] = [
            'chain' => self::COMMENT_SIGNATURE . 'first_dstnat',
            'action' => 'dst-nat',
            'src-address-list' => self::BLOCKED_USERS,
            'dst-port' => 80,
            'to-ports' => $serverSuspendPort,
            'protocol' => 'tcp',
            'comment' => 'blocked_user_redirect',
            'to-addresses' => $serverIp,
            'out-interface' => '',
        ];

        $this->synchronizeSection(
            '/ip/firewall/nat',
            $rules,
            [
                'chain',
                'action',
                'comment',
                'jump-target',
                'dst-address',
                'src-address',
                'to-addresses',
                'to-ports',
                'out-interface',
            ],
            true
        );

        return $this;
    }

    public function readGeneralInformation(): Device
    {
        $this->readCommandsFromDevice();

        $this->readModelName();
        $this->readOsVersion();

        $this->readSnmp();

        return $this;
    }

    public function saveConfiguration(): Device
    {
        if ($this->device->getLastBackupTimestamp() >= (new \DateTime('-1 day'))) {
            return $this;
        }

        // prevent sync from failing when ssh is not allowed on router
        try {
            // connect using ssh because RouterOs backup file is downloaded using ssh
            parent::connect();
        } catch (LoginException $e) {
            $this->log($e->getMessage(), DeviceLog::STATUS_ERROR);

            return $this;
        }

        $this->api->comm(
            self::SECTION_SYSTEM_BACKUP,
            ['name' => self::BACKUP_FILENAME]
        );

        $backupDirectory = $this->file->getDeviceBackupDirectory($this->device);
        $filename = sprintf('%s.backup', self::BACKUP_FILENAME);

        try {
            $data = $this->downloadFileSsh($filename);
        } catch (\Exception $exception) {
            $this->log($exception->getMessage(), DeviceLog::STATUS_ERROR);

            return $this;
        }

        $filename = sprintf('%d.backup', time());
        $this->file->save($backupDirectory, $filename, $data);

        $this->device->setLastBackupTimestamp(new \DateTime());

        $this->deleteOldBackups($backupDirectory);

        return $this;
    }

    public function searchForInterfaces(): Device
    {
        $interfaces = $this->getRawSectionList(self::SECTION_INTERFACE, ['name', 'type', 'mtu', 'mac-address']);
        $hasWireless = false;
        foreach ($interfaces as $interface) {
            if (
                Strings::contains($interface['type'], 'wireless')
                || Strings::contains($interface['type'], 'wlan')
            ) {
                $hasWireless = true;
            }
        }
        $this->routerIps = $this->getRawSectionList(self::SECTION_IP_ADDRESS, ['address', 'interface']);
        $this->wirelessInterfaces = $hasWireless
            ? $this->getRawSectionList(
                self::SECTION_INTERFACE_WIRELESS,
                [
                    'name',
                    'interface-type',
                    'ssid',
                    'frequency',
                    'band',
                    'channel-width',
                    'wireless-protocol',
                    'mac-address',
                    'mode',
                    'security-profile',
                ]
            )
            : [];

        $activeInterfaceInternalId = [];
        $routerAddressList = [];
        $wirelessInterfaces = [];

        foreach ($this->wirelessInterfaces as $interface) {
            $wirelessInterfaces[$interface['name']] = $this->sanitizeSSID($interface['ssid'] ?? null);
        }

        foreach ($this->routerIps as $row) {
            list($address, $netmask) = explode('/', $row['address'], 2);

            $ipAddress = new IpAddress();
            $ipAddress->ip = $address;
            $ipAddress->ipInt = ip2long($address);
            $ipAddress->netmask = (int) $netmask;

            $routerAddressList[$row['interface']][$ipAddress->ipInt] = $ipAddress;
            ksort($routerAddressList[$row['interface']]);
        }

        foreach ($interfaces as $interface) {
            $activeInterfaceInternalId[] = $interface['.id'];

            $deviceInterface = $this->matchInterfaceByField('internalId', $interface['.id']);

            if (
                null === $deviceInterface
                && ! in_array($interface['type'], self::SKIP_MAC_MATCHING_FOR_TYPES, true)
                && array_key_exists('mac-address', $interface)
            ) {
                $deviceInterface = $this->matchInterfaceByMacAddress(Mac::formatView($interface['mac-address']));
            }

            if (null === $deviceInterface) {
                $deviceInterface = $this->findDeviceInterfaceByIpAddress($routerAddressList, $interface['name']);
            }

            if (null === $deviceInterface && array_key_exists($interface['name'], $wirelessInterfaces)) {
                $deviceInterface = $this->matchInterfaceByField('ssid', $wirelessInterfaces[$interface['name']]);
            }

            if (null === $deviceInterface) {
                $deviceInterface = new DeviceInterface();
                $deviceInterface->setName($interface['name']);
                $deviceInterface->setDevice($this->device);

                $this->logAddedNewInterface($interface['name']);
                $this->em->persist($deviceInterface);
            }

            $this->setDeviceInterfaceType($interface['type'], $deviceInterface);

            $this->updateEntityAttribute(
                $deviceInterface,
                $interface,
                'name',
                'internalName'
            );

            $this->updateEntityAttribute(
                $deviceInterface,
                $interface,
                '.id',
                'internalId'
            );

            $this->updateEntityAttribute(
                $deviceInterface,
                $interface,
                'type',
                'internalType'
            );

            $this->updateEntityAttribute(
                $deviceInterface,
                $interface,
                'mac-address',
                'macAddress'
            );

            if (array_key_exists('mtu', $interface)) {
                $interface['mtu'] = (int) $interface['mtu'];
                $this->updateEntityAttribute(
                    $deviceInterface,
                    $interface,
                    'mtu'
                );
            }
        }

        $removedInterfaces = $this->deviceInterfaceRepository->removeInterfaceByInternalId(
            $this->device,
            $activeInterfaceInternalId
        );

        foreach ($removedInterfaces as $interfaceName) {
            $message = $this->translator->trans('Removed interface %name%', ['%name%' => $interfaceName]);
            $this->log($message, DeviceLog::STATUS_WARNING);
        }

        $this->em->flush();
        $this->em->refresh($this->device);

        return $this;
    }

    public function searchForWirelessInterfaces(): Device
    {
        foreach ($this->device->getNotDeletedInterfaces() as $deviceInterface) {
            if ($deviceInterface->getType() !== DeviceInterface::TYPE_WIRELESS) {
                continue;
            }

            foreach ($this->wirelessInterfaces as $key => $interface) {
                if ($interface['name'] === $deviceInterface->getInternalName() &&
                    Mac::format($interface['mac-address']) === Mac::format($deviceInterface->getMacAddress())
                ) {
                    $this->updateEntityAttribute(
                        $deviceInterface,
                        $interface,
                        'interface-type',
                        'interfaceModel'
                    );

                    $this->updateEntityAttribute(
                        $deviceInterface,
                        $interface,
                        'band'
                    );

                    if (array_key_exists('channel-width', $interface)) {
                        $interface['channel-width'] = intval($interface['channel-width']);
                        $this->updateEntityAttribute(
                            $deviceInterface,
                            $interface,
                            'channel-width',
                            'channelWidth'
                        );
                    }

                    $this->updateEntityAttribute(
                        $deviceInterface,
                        $interface,
                        'wireless-protocol',
                        'wirelessProtocol'
                    );

                    $this->updateEntityAttribute(
                        $deviceInterface,
                        $interface,
                        'ssid'
                    );

                    switch ($interface['mode'] ?? null) {
                        case DeviceInterface::AP_BRIDGE:
                            $interface['mode'] = DeviceInterface::MODE_AP;
                            break;
                        case DeviceInterface::ALIGMENT_ONLY:
                            $interface['mode'] = DeviceInterface::MODE_ALLIGMENT_ONLY;
                            break;
                        case DeviceInterface::BRIDGE:
                            $interface['mode'] = DeviceInterface::MODE_BRIDGE;
                            break;
                        case DeviceInterface::NSTREME_DUAL_SLAVE:
                            $interface['mode'] = DeviceInterface::MODE_NSTREAM_DUAL_SLAVE;
                            break;
                        case DeviceInterface::STATION:
                            $interface['mode'] = DeviceInterface::MODE_STATION;
                            break;
                        case DeviceInterface::STATION_BRIDGE:
                            $interface['mode'] = DeviceInterface::MODE_STATION_BRIDGE;
                            break;
                        case DeviceInterface::STATION_PSEUDOBRIDGE:
                            $interface['mode'] = DeviceInterface::MODE_STATION_PSEUDOBRIDGE;
                            break;
                        case DeviceInterface::STATION_PSEUDOBRIDGE_CLONE:
                            $interface['mode'] = DeviceInterface::MODE_STATION_PSEUDOBRIDGE_CLONE;
                            break;
                        case DeviceInterface::STATION_WDS:
                            $interface['mode'] = DeviceInterface::MODE_STATION_WDS;
                            break;
                        case DeviceInterface::WDS_SLAVE:
                            $interface['mode'] = DeviceInterface::MODE_STATION_WDS_SLAVE;
                            break;
                        default:
                            $interface['mode'] = DeviceInterface::MODE_UNKNOWN;
                    }

                    $this->updateEntityAttribute(
                        $deviceInterface,
                        $interface,
                        'mode'
                    );

                    if (! array_key_exists('frequency', $interface) || ! is_numeric($interface['frequency'])) {
                        $interface['frequency'] = 0;
                    } else {
                        $interface['frequency'] = (int) $interface['frequency'];
                    }

                    $this->updateEntityAttribute(
                        $deviceInterface,
                        $interface,
                        'frequency'
                    );
                }
            }
        }

        return $this;
    }

    public function searchForWirelessSecurity(): Device
    {
        if (empty($this->wirelessInterfaces)) {
            return $this;
        }

        $attributes = [
            'name',
            'authentication-types',
            'wpa-pre-shared-key',
            'wpa2-pre-shared-key',
            'unicast-ciphers',
            'group-ciphers',
            'mode',
        ];

        $profiles = $this->getRawSectionList(self::SECTION_INTERFACE_WIRELESS_SECURITY_PROFILES, $attributes);

        foreach ($profiles as $profile) {
            foreach ($this->wirelessInterfaces as $key => $interface) {
                if ($interface['security-profile'] !== $profile['name']) {
                    continue;
                }

                $deviceInterface = $this->matchInterfaceByField('internalId', $interface['.id']);
                if (! $deviceInterface) {
                    continue;
                }

                $encryptionTypes = explode(',', $profile['authentication-types']);

                $code = 0b10;

                $code = $code + (in_array('wpa2-eap', $encryptionTypes, true) ? 0b0001 : 0b0000);
                $code = $code + (in_array('wpa-eap', $encryptionTypes, true) ? 0b0010 : 0b0000);
                $code = $code + (in_array('wpa2-psk', $encryptionTypes, true) ? 0b0100 : 0b0000);
                $code = $code + (in_array('wpa-psk', $encryptionTypes, true) ? 0b1000 : 0b0000);

                $profile['encryptionType'] = $code;

                $this->updateEntityAttribute(
                    $deviceInterface,
                    $profile,
                    'encryptionType'
                );

                $unicastCiphers = explode(',', $profile['unicast-ciphers']);
                $aes = in_array('aes-ccm', $unicastCiphers, true);
                $tkip = in_array('tkip', $unicastCiphers, true);

                if ($aes && $tkip) {
                    $profile['unicastCiphers'] = DeviceInterface::CIPHER_AES_TKIP;
                } elseif ($aes && ! $tkip) {
                    $profile['unicastCiphers'] = DeviceInterface::CIPHER_AES;
                } elseif (! $aes && $tkip) {
                    $profile['unicastCiphers'] = DeviceInterface::CIPHER_TKIP;
                }

                $this->updateEntityAttribute(
                    $deviceInterface,
                    $profile,
                    'unicastCiphers'
                );

                $groupCiphers = explode(',', $profile['group-ciphers']);
                $aes = in_array('aes-ccm', $groupCiphers, true);
                $tkip = in_array('tkip', $groupCiphers, true);

                if ($aes && $tkip) {
                    $profile['groupCiphers'] = DeviceInterface::CIPHER_AES_TKIP;
                } elseif ($aes && ! $tkip) {
                    $profile['groupCiphers'] = DeviceInterface::CIPHER_AES;
                } elseif (! $aes && $tkip) {
                    $profile['groupCiphers'] = DeviceInterface::CIPHER_TKIP;
                }

                $this->updateEntityAttribute(
                    $deviceInterface,
                    $profile,
                    'groupCiphers'
                );

                switch ($profile['mode']) {
                    case 'dynamic-keys':
                        $profile['encryptionMode'] = DeviceInterface::ENCRYPTION_MODE_DYNAMIC_KEYS;
                        break;
                    case 'static-keys-optional':
                        $profile['encryptionMode'] = DeviceInterface::ENCRYPTION_MODE_STATIC_KEYS_OPTIONAL;
                        break;
                    case 'static-keys-required':
                        $profile['encryptionMode'] = DeviceInterface::ENCRYPTION_MODE_STATIC_KEYS_REQUIRED;
                        break;
                }

                $this->updateEntityAttribute(
                    $deviceInterface,
                    $profile,
                    'encryptionMode'
                );

                $this->updateEntityAttribute(
                    $deviceInterface,
                    $profile,
                    'wpa-pre-shared-key',
                    'encryptionKeyWpa',
                    true
                );

                $this->updateEntityAttribute(
                    $deviceInterface,
                    $profile,
                    'wpa2-pre-shared-key',
                    'encryptionKeyWpa2',
                    true
                );
            }
        }

        foreach ($this->wirelessInterfaces as $key => $interface) {
            if ($interface['security-profile'] !== 'disabled') {
                continue;
            }

            $deviceInterface = $this->matchInterfaceByField('internalId', $interface['.id']);

            if (! $deviceInterface) {
                continue;
            }

            $profile['encryptionType'] = DeviceInterface::ENCRYPTION_TYPE_NONE;
            $profile['encryptionMode'] = DeviceInterface::ENCRYPTION_MODE_NONE;
            $profile['encryptionMode'] = DeviceInterface::CIPHER_NONE;
            $profile['groupCiphers'] = DeviceInterface::CIPHER_NONE;
            $profile['unicastCiphers'] = DeviceInterface::CIPHER_NONE;

            $this->updateEntityAttribute(
                $deviceInterface,
                $profile,
                'encryptionType'
            );

            $this->updateEntityAttribute(
                $deviceInterface,
                $profile,
                'encryptionMode'
            );

            $this->updateEntityAttribute(
                $deviceInterface,
                $profile,
                'wpa-pre-shared-key',
                'encryptionKeyWpa',
                true
            );

            $this->updateEntityAttribute(
                $deviceInterface,
                $profile,
                'wpa2-pre-shared-key',
                'encryptionKeyWpa2',
                true
            );
        }

        return $this;
    }

    public function searchForInterfaceIpsChangedInterface(): Device
    {
        $attributes = ['address', 'interface'];
        $ips = $this->getRawSectionList(self::SECTION_IP_ADDRESS, $attributes);

        if (empty($ips)) {
            return $this;
        }

        foreach ($ips as $ip) {
            list($ipAddress, $netmask) = explode('/', $ip['address'], 2);
            $ipAddress = ip2long($ipAddress);
            $netmask = (int) $netmask;

            $this->changeIpOnInterface($ipAddress, $netmask, $ip['.id'], $ip['interface']);
        }

        return $this;
    }

    public function searchForInterfaceIps(): Device
    {
        if (empty($this->routerIps)) {
            return $this;
        }

        $activeIpInternalId = [];
        $ipsForRemoval = [];

        foreach ($this->device->getNotDeletedInterfaces() as $deviceInterface) {
            $existingIps = $deviceInterface->getInterfaceIps();
            $remoteIps = new ArrayCollection();

            foreach ($this->routerIps as $key => $ip) {
                if (! in_array($ip['.id'], $activeIpInternalId, true)) {
                    $activeIpInternalId[] = $ip['.id'];
                }

                list($ipAddress, $netmask) = explode('/', $ip['address'], 2);
                $ip['ipAddress'] = ip2long($ipAddress);
                $ip['netmask'] = (int) $netmask;

                /** @var DeviceInterfaceIp $deviceInterfaceIp */
                $deviceInterfaceIp = $this->deviceInterfaceIpRepository->findOneBy(
                    [
                        'ipRange.ipAddress' => $ip['ipAddress'],
                        'interface' => $deviceInterface,
                    ]
                );

                if (null === $deviceInterfaceIp && $deviceInterface->getInternalName() === $ip['interface']) {
                    $deviceInterfaceIp = new DeviceInterfaceIp();
                    $deviceInterfaceIp
                        ->setInterface($deviceInterface)
                        ->setInternalId($ip['.id'])
                        ->setNatPublicIp(null)
                        ->getIpRange()
                        ->setCidr(ip2long($ipAddress), (int) $netmask);

                    $this->setInterfaceIpAccessible($deviceInterfaceIp);

                    if ($ipAddress === $this->ip) {
                        $deviceInterfaceIp->setWasLastConnectionSuccessful(true);
                    }

                    $this->em->persist($deviceInterfaceIp);

                    $this->logAddedNewIp($deviceInterface, $deviceInterfaceIp);
                } elseif ($deviceInterface->getInternalName() === $ip['interface']) {
                    $ipRange = $deviceInterfaceIp->getIpRange();
                    $from = $ipRange->getRangeForView();
                    $ipRange->setCidr($ip['ipAddress'], $ip['netmask']);
                    if ($ipRange->getRangeForView() !== $from) {
                        $this->logChangedAttribute($deviceInterfaceIp, 'ipRange', $from, $ipRange->getRangeForView());
                    }

                    $this->updateEntityAttribute(
                        $deviceInterfaceIp,
                        $ip,
                        '.id',
                        'internalId'
                    );
                }

                $remoteIps->add($deviceInterfaceIp);
            }

            foreach ($existingIps as $existingIp) {
                if (! $remoteIps->contains($existingIp)) {
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
        if (empty($this->wirelessInterfaces)) {
            return $this;
        }

        $attributes = [
            'interface',
            'mac-address',
            'ap',
            'rx-rate',
            'tx-rate',
            'uptime',
            'last-activity',
            'signal-strength',
            'signal-to-noise',
            'tx-ccq',
            'last-ip',
        ];

        $signal = -100;
        $ccq = 0;
        $rxRate = 0;
        $txRate = 0;

        $wirelessStatisticsShortTerm = new WirelessStatisticsShortTerm();
        $wirelessStatisticsShortTerm->setDevice($this->device);

        $registeredDevices = $this->getRawSectionList(self::SECTION_INTERFACE_WIRELESS_REGISTRATION_TABLE, $attributes);

        if (empty($registeredDevices)) {
            if ($this->device->getCreateSignalStatistics()) {
                $wirelessStatisticsShortTerm
                    ->setCcq($ccq)
                    ->setRxRate($rxRate)
                    ->setTxRate($txRate)
                    ->setSignal($signal)
                    ->setTime(new \DateTime());

                $this->em->persist($wirelessStatisticsShortTerm);
            }

            return $this;
        }

        foreach ($registeredDevices as $device) {
            if ($device['ap'] === 'true') {
                continue;
            }

            $deviceInterface = $this->matchInterfaceByField('internalName', $device['interface']);

            if (! $deviceInterface) {
                continue;
            }

            $lastIp = ($device['last-ip'] ?? null) ? ip2long($device['last-ip']) : null;
            $macAddress = Mac::format($device['mac-address']);

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
                ->setRxRate($device['rx-rate'])
                ->setTxRate($device['tx-rate'])
                ->setUptime($device['uptime'])
                ->setLastActivity($device['last-activity'])
                ->setSignalStrength($device['signal-strength'])
                ->setSignalToNoise($device['signal-to-noise'])
                ->setTxCcq($device['tx-ccq'] ?? null)
                ->setLastIp($device['last-ip'] ?? null);

            $this->em->persist($unknownServiceDevice);

            $signal = intval($device['signal-strength']) > $signal ? intval($device['signal-strength']) : $signal;
            $rxRate = intval($device['rx-rate']) > $rxRate ? intval($device['rx-rate']) : $rxRate;
            $txRate = intval($device['tx-rate']) > $txRate ? intval($device['tx-rate']) : $txRate;
            if (($device['tx-ccq'] ?? false) && $device['tx-ccq'] > $ccq) {
                $ccq = (int) $device['tx-ccq'];
            }
        }

        $wirelessStatisticsShortTerm
            ->setCcq($ccq)
            ->setRxRate($rxRate)
            ->setTxRate($txRate)
            ->setSignal($signal)
            ->setTime(new \DateTime());

        $this->em->persist($wirelessStatisticsShortTerm);

        return $this;
    }

    protected function readModelName()
    {
        $this->device->setModelName($this->resource['board-name']);
    }

    protected function readOsVersion()
    {
        $this->device->setOsVersion($this->resource['version']);
    }

    protected function readCommandsFromDevice()
    {
        $this->resource = current($this->getRawSectionList(self::SECTION_SYSTEM_RESOURCE));
    }

    protected function syncFirewallAddressList(string $name, array $localList): bool
    {
        $this->connect();

        $addressListRows = [];
        foreach ($localList as $ip) {
            //for each ip address => create address list row
            $addressListRows[] = [
                'list' => self::BLOCKED_USERS,
                'address' => $ip,
                'comment' => 'blocked_users',
            ];
        }

        $this->synchronizeIpFirewallAddressListSection($addressListRows);

        return true;
    }

    /**
     * @param int|string $from
     * @param int|string $to
     * @param array      $attributes
     */
    protected function formatLogValues(string $attributeName, $from, $to, $attributes): array
    {
        list($from, $to) = parent::formatLogValues($attributeName, $from, $to, $attributes);

        switch ($attributeName) {
            case 'ipAddress':
                $from = sprintf('%s/%d', long2ip($from), $attributes['netmask']);
                $to = sprintf('%s/%d', long2ip($to), $attributes['netmask']);
                break;
            case 'netmask':
                $from = sprintf('%s/%d', long2ip($attributes['ipAddress']), $from);
                $to = sprintf('%s/%d', long2ip($attributes['ipAddress']), $to);
                break;
        }

        return [$from, $to];
    }

    private function readSnmp()
    {
        $snmp = current($this->getRawSectionList(self::SECTION_SNMP));

        if ($snmp['enabled'] === 'true') {
            $snmpCommunity = current($this->getRawSectionList(self::SECTION_SNMP_COMMUNITY));
            $this->device->setSnmpCommunity($snmpCommunity['name']);
        } else {
            $this->device->setSnmpCommunity(null);
        }
    }

    private function createIndex(array $arr, array $attrs): array
    {
        $index = [];
        foreach ($arr as $row) {
            $key = $this->createIndexKey($row, $attrs);
            $index[$key] = $row;
        }

        return $index;
    }

    private function createIndexKey(array $item, array $attrs): string
    {
        $res = '';
        foreach ($attrs as $attr) {
            $res .= '_' . (array_key_exists($attr, $item) ? $item[$attr] : '');
        }

        return $res;
    }

    private function removeFromSection(string $section, array $idList): array
    {
        if (! $idList) {
            return [];
        }

        return $this->api->comm(
            $section . '/remove',
            [
                '.id' => implode(',', $idList),
            ]
        );
    }

    private function addToSection(string $section, array $list, bool $addComment = true)
    {
        foreach ($list as $row) {
            //remove empty attributes
            foreach ($row as $key => $value) {
                if (! $value) {
                    unset($row[$key]);
                }
            }

            if ($addComment) {
                $row['comment'] = self::COMMENT_SIGNATURE . (isset($row['comment']) ? $row['comment'] : '');
            }
            $this->api->comm(
                $section . '/add',
                $row
            );
        }
    }

    /**
     * @param object $entity
     */
    private function updateEntityAttribute(
        $entity,
        array $attributes,
        string $attributeName,
        string $method = null,
        bool $encryptValue = false
    ): bool {
        if (! array_key_exists($attributeName, $attributes)) {
            return false;
        }

        $getter = sprintf('get%s', ucwords($method ? $method : $attributeName));
        $setter = sprintf('set%s', ucwords($method ? $method : $attributeName));

        if ((! $encryptValue && $entity->$getter() !== $attributes[$attributeName]) ||
            ($encryptValue && $this->encryption->decrypt($entity->$getter()) != $attributes[$attributeName])
        ) {
            if ($encryptValue) {
                $attributes[$attributeName] = $this->encryption->encrypt($attributes[$attributeName]);
            } else {
                $from = $entity->$getter();
                $to = $attributes[$attributeName];

                switch ($attributeName) {
                    case self::MAC_ADDRESS:
                        $from = Mac::format($from);
                        $to = Mac::format($to);
                        break;
                    case self::SSID:
                    case self::ESSID:
                        $from = $this->sanitizeSSID($from);
                        $attributes[$attributeName] = $to = $this->sanitizeSSID($to);
                        break;
                }

                list($from, $to) = $this->formatLogValues($attributeName, $from, $to, $attributes);

                $this->logChangedAttribute($entity, $attributeName, $from, $to, $method);
            }

            $entity->$setter($attributes[$attributeName]);

            return true;
        }

        return false;
    }

    private function setDeviceInterfaceType(string $routerOsType, DeviceInterface $deviceInterface)
    {
        switch ($routerOsType) {
            case DeviceInterface::WLAN:
                $deviceInterface->setType(DeviceInterface::TYPE_WIRELESS);
                break;
            case DeviceInterface::ETHER:
                $deviceInterface->setType(DeviceInterface::TYPE_ETHERNET);
                break;
            case DeviceInterface::VLAN:
                $deviceInterface->setType(DeviceInterface::TYPE_VLAN);
                break;
            case DeviceInterface::MESH:
                $deviceInterface->setType(DeviceInterface::TYPE_MESH);
                break;
            case DeviceInterface::BOND:
                $deviceInterface->setType(DeviceInterface::TYPE_BONDING);
                break;
            case DeviceInterface::BRIDGE:
                $deviceInterface->setType(DeviceInterface::TYPE_BRIDGE);
                break;
            case DeviceInterface::CAP:
                $deviceInterface->setType(DeviceInterface::TYPE_CAP);
                break;
            case DeviceInterface::GRE_TUNNEL:
                $deviceInterface->setType(DeviceInterface::TYPE_GRE);
                break;
            case DeviceInterface::GRE6_TUNNEL:
                $deviceInterface->setType(DeviceInterface::TYPE_GRE6);
                break;
            case DeviceInterface::L2TP_IN:
            case DeviceInterface::L2TP_OUT:
                $deviceInterface->setType(DeviceInterface::TYPE_L2TP);
                break;
            case DeviceInterface::OVPN_IN:
            case DeviceInterface::OVPN_OUT:
                $deviceInterface->setType(DeviceInterface::TYPE_OVPN);
                break;
            case DeviceInterface::PPPOE_IN:
            case DeviceInterface::PPPOE_OUT:
                $deviceInterface->setType(DeviceInterface::TYPE_PPPOE);
                break;
            case DeviceInterface::PPTP_IN:
            case DeviceInterface::PPTP_OUT:
                $deviceInterface->setType(DeviceInterface::TYPE_PPTP);
                break;
            case DeviceInterface::SSTP_IN:
            case DeviceInterface::SSTP_OUT:
                $deviceInterface->setType(DeviceInterface::TYPE_SSTP);
                break;
            case DeviceInterface::VPLS:
                $deviceInterface->setType(DeviceInterface::TYPE_VPLS);
                break;
            case DeviceInterface::TRAFFIC_ENG:
                $deviceInterface->setType(DeviceInterface::TYPE_TRAFFIC_ENG);
                break;
            case DeviceInterface::VRRP:
                $deviceInterface->setType(DeviceInterface::TYPE_VRRP);
                break;
            case DeviceInterface::WDS:
                $deviceInterface->setType(DeviceInterface::TYPE_WDS);
                break;
            default:
                $deviceInterface->setType(DeviceInterface::TYPE_ETHERNET);
        }
    }
}
