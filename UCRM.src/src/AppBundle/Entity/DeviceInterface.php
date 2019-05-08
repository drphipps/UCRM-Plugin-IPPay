<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use AppBundle\Component\Validator\Constraints as CustomAssert;
use AppBundle\Util\Mac;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\DeviceInterfaceRepository")
 * @ORM\Table(
 *      indexes={
 *          @ORM\Index(columns={"deleted_at"}),
 *      }
 * )
 */
class DeviceInterface implements LoggableInterface, ParentLoggableInterface, SoftDeleteLoggableInterface
{
    use SoftDeleteableTrait;

    const TYPE_UNKNOWN = 0;
    const TYPE_WIRELESS = 1;
    const TYPE_ETHERNET = 2;
    const TYPE_VLAN = 3;
    const TYPE_MESH = 4;
    const TYPE_BONDING = 5;
    const TYPE_BRIDGE = 6;
    const TYPE_CAP = 7;
    const TYPE_GRE = 8;
    const TYPE_GRE6 = 9;
    const TYPE_L2TP = 10;
    const TYPE_OVPN = 11;
    const TYPE_PPPOE = 12;
    const TYPE_PPTP = 13;
    const TYPE_SSTP = 14;
    const TYPE_VPLS = 15;
    const TYPE_TRAFFIC_ENG = 16;
    const TYPE_VRRP = 17;
    const TYPE_WDS = 18;

    const TYPES = [
        self::TYPE_WIRELESS => 'wireless',
        self::TYPE_ETHERNET => 'ethernet',
        self::TYPE_VLAN => 'vlan',
        self::TYPE_MESH => 'mesh',
        self::TYPE_BONDING => 'bonding',
        self::TYPE_BRIDGE => 'bridge',
        self::TYPE_CAP => 'cap',
        self::TYPE_GRE => 'gre',
        self::TYPE_GRE6 => 'gre6',
        self::TYPE_L2TP => 'l2tp',
        self::TYPE_OVPN => 'ovpn',
        self::TYPE_PPPOE => 'pppoe',
        self::TYPE_PPTP => 'pptp',
        self::TYPE_SSTP => 'sstp',
        self::TYPE_VPLS => 'vpls',
        self::TYPE_TRAFFIC_ENG => 'traffic eng',
        self::TYPE_VRRP => 'vrrp',
        self::TYPE_WDS => 'wds',
    ];

    const ENCRYPTION_TYPE_NONE = 0;
    const ENCRYPTION_TYPE_WEP = 1;
    const ENCRYPTION_TYPE_WPA = 2;
    const ENCRYPTION_TYPE_WPAPSK_WPA2PSK_WPAEAP_WPA2EAP = 17;
    const ENCRYPTION_TYPE_WPAPSK_WPA2PSK_WPAEAP = 16;
    const ENCRYPTION_TYPE_WPAPSK_WPA2PSK_WPA2EAP = 15;
    const ENCRYPTION_TYPE_WPAPSK_WPA2PSK = 14;
    const ENCRYPTION_TYPE_WPAPSK_WPAEAP_WPA2EAP = 13;
    const ENCRYPTION_TYPE_WPAPSK_WPAEAP = 12;
    const ENCRYPTION_TYPE_WPAPSK_WPA2EAP = 11;
    const ENCRYPTION_TYPE_WPAPSK = 10;
    const ENCRYPTION_TYPE_WPA2PSK_WPAEAP_WPA2EAP = 9;
    const ENCRYPTION_TYPE_WPA2PSK_WPAEAP = 8;
    const ENCRYPTION_TYPE_WPA2PSK_WPA2EAP = 7;
    const ENCRYPTION_TYPE_WPA2PSK = 6;
    const ENCRYPTION_TYPE_WPAEAP_WPA2EAP = 5;
    const ENCRYPTION_TYPE_WPAEAP = 4;
    const ENCRYPTION_TYPE_WPA2EAP = 3;

    const ENCRYPTION_TYPES = [
        self::ENCRYPTION_TYPE_NONE => 'None',
        self::ENCRYPTION_TYPE_WEP => 'WEP',
        self::ENCRYPTION_TYPE_WPA => 'WPA',
        self::ENCRYPTION_TYPE_WPA2EAP => 'WPA2EAP',
        self::ENCRYPTION_TYPE_WPAEAP => 'WPAEAP',
        self::ENCRYPTION_TYPE_WPAEAP_WPA2EAP => 'WPAEAP / WPA2EAP',
        self::ENCRYPTION_TYPE_WPA2PSK => 'WPA2PSK',
        self::ENCRYPTION_TYPE_WPA2PSK_WPA2EAP => 'WPA2PSK / WPA2EAP',
        self::ENCRYPTION_TYPE_WPA2PSK_WPAEAP => 'WPA2PSK / WPAEAP',
        self::ENCRYPTION_TYPE_WPA2PSK_WPAEAP_WPA2EAP => 'WPA2PSK / WPAEAP / WPA2EAP',
        self::ENCRYPTION_TYPE_WPAPSK => 'WPAPSK',
        self::ENCRYPTION_TYPE_WPAPSK_WPA2EAP => 'WPAPSK / WPA2EAP',
        self::ENCRYPTION_TYPE_WPAPSK_WPAEAP => 'WPAPSK / WPAEAP',
        self::ENCRYPTION_TYPE_WPAPSK_WPAEAP_WPA2EAP => 'WPAPSK / WPAEAP / WPA2EAP',
        self::ENCRYPTION_TYPE_WPAPSK_WPA2PSK => 'WPAPSK / WPA2PSK',
        self::ENCRYPTION_TYPE_WPAPSK_WPA2PSK_WPA2EAP => 'WPAPSK / WPA2PSK / WPA2EAP',
        self::ENCRYPTION_TYPE_WPAPSK_WPA2PSK_WPAEAP => 'WPAPSK / WPA2PSK / WPAEAP',
        self::ENCRYPTION_TYPE_WPAPSK_WPA2PSK_WPAEAP_WPA2EAP => 'WPAPSK / WPA2PSK / WPAEAP / WPA2EAP',
    ];

    const ENCRYPTION_MODE_NONE = 0;
    const ENCRYPTION_MODE_DYNAMIC_KEYS = 1;
    const ENCRYPTION_MODE_STATIC_KEYS_OPTIONAL = 2;
    const ENCRYPTION_MODE_STATIC_KEYS_REQUIRED = 3;

    const ENCRYPTION_MODE_TYPES = [
        self::ENCRYPTION_MODE_NONE => 'None',
        self::ENCRYPTION_MODE_DYNAMIC_KEYS => 'Dynamic keys',
        self::ENCRYPTION_MODE_STATIC_KEYS_OPTIONAL => 'Static keys optional',
        self::ENCRYPTION_MODE_STATIC_KEYS_REQUIRED => 'Static keys required',
    ];

    const CIPHER_NONE = 0;
    const CIPHER_AES = 1;
    const CIPHER_TKIP = 2;
    const CIPHER_AES_TKIP = 3;

    const CIPHER_TYPES = [
        self::CIPHER_NONE => 'None',
        self::CIPHER_AES => 'AES',
        self::CIPHER_TKIP => 'TKIP',
        self::CIPHER_AES_TKIP => 'AES / TKIP',
    ];

    const POLARIZATION_VERTICAL = 1;
    const POLARIZATION_HORIZONTAL = 2;
    const POLARIZATION_BOTH = 3;

    const POLARIZATION_TYPES = [
        self::POLARIZATION_VERTICAL => 'Vertical',
        self::POLARIZATION_HORIZONTAL => 'Horizontal',
        self::POLARIZATION_BOTH => 'Both',
    ];

    const MODE_UNKNOWN = 0;
    const MODE_AP = 1;
    const MODE_ALLIGMENT_ONLY = 2;
    const MODE_BRIDGE = 3;
    const MODE_NSTREAM_DUAL_SLAVE = 4;
    const MODE_STATION = 5;
    const MODE_STATION_BRIDGE = 6;
    const MODE_STATION_PSEUDOBRIDGE = 7;
    const MODE_STATION_PSEUDOBRIDGE_CLONE = 8;
    const MODE_STATION_WDS = 9;
    const MODE_STATION_WDS_SLAVE = 10;
    const MODE_ACCESS_POINT_PTP = 11;
    const MODE_ACCESS_POINT_PTMP_AIRMAX_AC = 12;
    const MODE_ACCESS_POINT_PTMP_AIRMAX_MIXED = 13;
    const MODE_STATION_PTP = 14;
    const MODE_STATION_PTMP = 15;
    const MODE_ACCESS_POINT_REPEATER = 16;

    const MODE_TYPES = [
        self::MODE_UNKNOWN => 'Unknown',
        self::MODE_AP => 'Access point',
        self::MODE_ACCESS_POINT_REPEATER => 'Access point Repeater',
        self::MODE_ACCESS_POINT_PTP => 'Access point to point',
        self::MODE_ACCESS_POINT_PTMP_AIRMAX_AC => 'Access point to multipoint airMAX AC',
        self::MODE_ACCESS_POINT_PTMP_AIRMAX_MIXED => 'Access point to multipoint airMAX Mixed',
        self::MODE_ALLIGMENT_ONLY => 'Aligment only',
        self::MODE_BRIDGE => 'Bridge',
        self::MODE_NSTREAM_DUAL_SLAVE => 'Nstream dual slave',
        self::MODE_STATION => 'Station',
        self::MODE_STATION_PTP => 'Station point to point',
        self::MODE_STATION_PTMP => 'Station point to multipoint',
        self::MODE_STATION_BRIDGE => 'Station bridge',
        self::MODE_STATION_PSEUDOBRIDGE => 'Station pseudobridge',
        self::MODE_STATION_PSEUDOBRIDGE_CLONE => 'Station pseudobridge clone',
        self::MODE_STATION_WDS => 'Station WDS',
        self::MODE_STATION_WDS_SLAVE => 'Station WDS slave',
    ];

    const WIRELESS_NAME = 'ath0';
    const BRIDGE_NAME = 'br0';
    const ETHERNET_NAME = 'eth0';
    const WIFI_NAME = 'wifi0';

    const ALIGMENT_ONLY = 'alignment-only';
    const AP = 'ap';
    const AP_BRIDGE = 'ap-bridge';
    const AP_PTMP = 'ap-ptmp';
    const AP_PTP = 'ap-ptp';
    const BOND = 'bond';
    const BRIDGE = 'bridge';
    const CAP = 'cap';
    const ETHER = 'ether';
    const GRE6_TUNNEL = 'gre6-tunnel';
    const GRE_TUNNEL = 'gre-tunnel';
    const L2TP_IN = 'l2tp-ip';
    const L2TP_OUT = 'l2tp-out';
    const MESH = 'mesh';
    const NSTREME_DUAL_SLAVE = 'nstreme-dual-slave';
    const OVPN_IN = 'ovpn-in';
    const OVPN_OUT = 'ovpn-out';
    const PPPOE_IN = 'pppoe-in';
    const PPPOE_OUT = 'pppoe-out';
    const PPTP_IN = 'pptp-in';
    const PPTP_OUT = 'pptp-out';
    const SSTP_IN = 'sstp-in';
    const SSTP_OUT = 'sstp-out';
    const STA = 'sta';
    const STA_PTMP = 'sta-ptmp';
    const STA_PTP = 'sta-ptp';
    const STATION = 'station';
    const STATION_BRIDGE = 'station-bridge';
    const STATION_PSEUDOBRIDGE = 'station-pseudobridge';
    const STATION_PSEUDOBRIDGE_CLONE = 'station-pseudobridge-clone';
    const STATION_WDS = 'station-wds';
    const TRAFFIC_ENG = 'traffic_eng';
    const VLAN = 'vlan';
    const VPLS = 'vpls';
    const VRRP = 'vrrp';
    const WDS = 'wds';
    const WDS_SLAVE = 'wds-slave';
    const WLAN = 'wlan';

    /**
     * @var int
     *
     * @ORM\Column(name="interface_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100)
     * @Assert\Length(max = 100)
     * @Assert\NotBlank()
     */
    protected $name;

    /**
     * @var int
     *
     * @Assert\NotNull()
     * @ORM\Column(name="type", type="integer")
     */
    protected $type;

    /**
     * @var string
     *
     * @ORM\Column(name="mac_address", type="string", length=17, nullable=true)
     * @Assert\Length(max = 17)
     * @CustomAssert\Mac()
     */
    protected $macAddress;

    /**
     * @var bool
     *
     * @ORM\Column(name="allow_client_connection", type="boolean", options={"default":true})
     */
    protected $allowClientConnection = true;

    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="text", nullable=true)
     */
    protected $notes;

    /**
     * @var bool
     *
     * @ORM\Column(name="enabled", type="boolean", options={"default":true})
     */
    protected $enabled = true;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ssid", type="string", length=32, nullable=true)
     * @Assert\Length(max = 32)
     */
    protected $ssid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="frequency", type="string", nullable=true)
     */
    protected $frequency;

    /**
     * @var int|null
     *
     * @ORM\Column(name="polarization", type="integer", nullable=true)
     */
    protected $polarization;

    /**
     * @var int|null
     *
     * @ORM\Column(name="encryption_type", type="integer", nullable=true)
     */
    protected $encryptionType;

    /**
     * @var string|null
     *
     * @ORM\Column(name="encryption_key_wpa", type="string", length=256, nullable=true)
     * @Assert\Length(max = 256)
     */
    protected $encryptionKeyWpa;

    /**
     * @var string|null
     *
     * @ORM\Column(name="encryption_key_wpa2", type="string", length=256, nullable=true)
     * @Assert\Length(max = 256)
     */
    protected $encryptionKeyWpa2;

    /**
     * @var Device
     *
     * @ORM\ManyToOne(targetEntity="Device", inversedBy="interfaces")
     * @ORM\JoinColumn(name="device_id", referencedColumnName="device_id", nullable=false)
     * @Assert\NotNull()
     */
    protected $device;

    /**
     * @var Collection|DeviceInterfaceIp[]
     *
     * @ORM\OneToMany(targetEntity="DeviceInterfaceIp", mappedBy="interface", cascade={"persist","remove"})
     * @Assert\Valid()
     */
    protected $interfaceIps;

    /**
     * @var Collection|ServiceDevice[]
     *
     * @ORM\OneToMany(targetEntity="ServiceDevice", mappedBy="interface", cascade={"remove"})
     */
    protected $serviceDevices;

    /**
     * @var string|null
     *
     * @ORM\Column(name="internal_id", type="string", length=128, nullable=true)
     * @Assert\Length(max = 128)
     */
    protected $internalId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="internal_name", type="string", length=128, nullable=true)
     * @Assert\Length(max = 128)
     */
    protected $internalName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="internal_type", type="string", length=128, nullable=true)
     * @Assert\Length(max = 128)
     */
    protected $internalType;

    /**
     * @var int|null
     *
     * @ORM\Column(name="mtu", type="integer", length=8, nullable=true)
     * @Assert\Length(max = 8)
     */
    protected $mtu;

    /**
     * @var string|null
     *
     * @ORM\Column(name="interface_model", type="string", length=128, nullable=true)
     * @Assert\Length(max = 128)
     */
    protected $interfaceModel;

    /**
     * @var string|null
     *
     * @ORM\Column(name="band", type="string", length=128, nullable=true)
     * @Assert\Length(max = 128)
     */
    protected $band;

    /**
     * @var int|null
     *
     * @ORM\Column(name="channel_width", type="integer", length=4, nullable=true)
     * @Assert\Length(max = 4)
     */
    protected $channelWidth;

    /**
     * @var string|null
     *
     * @ORM\Column(name="wireless_protocol", type="string", length=128, nullable=true)
     * @Assert\Length(max = 128)
     */
    protected $wirelessProtocol;

    /**
     * @var int|null
     *
     * @ORM\Column(name="mode", type="integer", length=16, nullable=true)
     * @Assert\Length(max = 16)
     */
    protected $mode;

    /**
     * @var int|null
     *
     * @ORM\Column(name="unicast_ciphers", type="integer", length=16, nullable=true)
     * @Assert\Length(max = 16)
     */
    protected $unicastCiphers;

    /**
     * @var int|null
     *
     * @ORM\Column(name="group_ciphers", type="integer", length=16, nullable=true)
     * @Assert\Length(max = 16)
     */
    protected $groupCiphers;

    /**
     * @var int|null
     *
     * @ORM\Column(name="encryption_mode", type="integer", length=16, nullable=true)
     * @Assert\Length(max = 16)
     */
    protected $encryptionMode;

    public function __construct()
    {
        $this->interfaceIps = new ArrayCollection();
        $this->serviceDevices = new ArrayCollection();
    }

    public function getNameForView(): string
    {
        return sprintf(
            '%s - %s',
            $this->getDevice()->getName(),
            $this->getName()
        );
    }

    /**
     * @return Collection|DeviceInterfaceIp[]
     */
    public function getInterfaceIps()
    {
        return $this->interfaceIps;
    }

    /**
     * @return array
     */
    public function getIps()
    {
        $ips = [];
        foreach ($this->interfaceIps as $interfaceIp) {
            $ips[] = [
                'ip' => long2ip($interfaceIp->getIpRange()->getIpAddress()),
                'netmask' => $interfaceIp->getIpRange()->getNetmask(),
            ];
        }

        return $ips;
    }

    /**
     * @return string
     */
    public function getIpsHumanize()
    {
        $ips = [];
        foreach ($this->getIps() as $ip) {
            $ips[] = sprintf('%s/%s', $ip['ip'], $ip['netmask']);
        }

        return implode(', ', $ips);
    }

    /**
     * @return $this
     */
    public function addInterfaceIp(DeviceInterfaceIp $interfaceIp)
    {
        $this->interfaceIps[] = $interfaceIp;
        $interfaceIp->setInterface($this);

        return $this;
    }

    /**
     * @return Device
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * @param Device $device
     *
     * @return DeviceInterface
     */
    public function setDevice(Device $device = null)
    {
        $this->device = $device;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     *
     * @return DeviceInterface
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param int $type
     *
     * @return DeviceInterface
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return DeviceInterface
     */
    public function setMacAddress(string $macAddress = null)
    {
        $this->macAddress = Mac::format($macAddress);

        return $this;
    }

    /**
     * @return string
     */
    public function getMacAddress()
    {
        return Mac::formatView($this->macAddress);
    }

    /**
     * @return DeviceInterface
     */
    public function setAllowClientConnection($allowClientConnection)
    {
        $this->allowClientConnection = (bool) $allowClientConnection;

        return $this;
    }

    /**
     * @return bool
     */
    public function getAllowClientConnection()
    {
        return $this->allowClientConnection;
    }

    /**
     * @param string $notes
     *
     * @return DeviceInterface
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @return DeviceInterface
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (bool) $enabled;

        return $this;
    }

    /**
     * @return bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param string $ssid
     *
     * @return DeviceInterface
     */
    public function setSsid($ssid)
    {
        $this->ssid = $ssid;

        return $this;
    }

    /**
     * @return string
     */
    public function getSsid()
    {
        return $this->ssid;
    }

    /**
     * @return DeviceInterface
     */
    public function setFrequency(string $frequency = null)
    {
        $this->frequency = $frequency;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFrequency()
    {
        return $this->frequency;
    }

    /**
     * @param int $polarization
     *
     * @return DeviceInterface
     */
    public function setPolarization($polarization)
    {
        $this->polarization = $polarization;

        return $this;
    }

    /**
     * @return int
     */
    public function getPolarization()
    {
        return $this->polarization;
    }

    /**
     * @param int $encryptionType
     *
     * @return DeviceInterface
     */
    public function setEncryptionType($encryptionType)
    {
        $this->encryptionType = $encryptionType;

        return $this;
    }

    /**
     * @return int
     */
    public function getEncryptionType()
    {
        return $this->encryptionType;
    }

    public function removeInterfaceIp(DeviceInterfaceIp $interfaceIp)
    {
        $this->interfaceIps->removeElement($interfaceIp);
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDeleteMessage()
    {
        $message['logMsg'] = [
            'message' => 'Interface %s deleted',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogArchiveMessage()
    {
        $message['logMsg'] = [
            'message' => 'Interface %s archived',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogRestoreMessage()
    {
        $message['logMsg'] = [
            'message' => 'Interface %s restored',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogInsertMessage()
    {
        $message['logMsg'] = [
            'message' => 'Interface %s added',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogIgnoredColumns()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getLogClient()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogSite()
    {
        return $this->getDevice()->getSite();
    }

    /**
     * {@inheritdoc}
     */
    public function getLogParentEntity()
    {
        return $this->getDevice();
    }

    /**
     * {@inheritdoc}
     */
    public function getLogUpdateMessage()
    {
        $message['logMsg'] = [
            'id' => $this->getId(),
            'message' => $this->getName(),
            'entity' => self::class,
        ];

        return $message;
    }

    /**
     * @param string $internalId
     *
     * @return DeviceInterface
     */
    public function setInternalId($internalId)
    {
        $this->internalId = $internalId;

        return $this;
    }

    /**
     * @return string
     */
    public function getInternalId()
    {
        return $this->internalId;
    }

    /**
     * @param string $internalName
     *
     * @return DeviceInterface
     */
    public function setInternalName($internalName)
    {
        $this->internalName = $internalName;

        return $this;
    }

    /**
     * @return string
     */
    public function getInternalName()
    {
        return $this->internalName;
    }

    /**
     * @param string $internalType
     *
     * @return DeviceInterface
     */
    public function setInternalType($internalType)
    {
        $this->internalType = $internalType;

        return $this;
    }

    /**
     * @return string
     */
    public function getInternalType()
    {
        return $this->internalType;
    }

    /**
     * @return DeviceInterface
     */
    public function setMtu(int $mtu)
    {
        $this->mtu = $mtu;

        return $this;
    }

    /**
     * @return int
     */
    public function getMtu()
    {
        return $this->mtu;
    }

    /**
     * @param string $interfaceModel
     *
     * @return DeviceInterface
     */
    public function setInterfaceModel($interfaceModel)
    {
        $this->interfaceModel = $interfaceModel;

        return $this;
    }

    /**
     * @return string
     */
    public function getInterfaceModel()
    {
        return $this->interfaceModel;
    }

    /**
     * @param string $band
     *
     * @return DeviceInterface
     */
    public function setBand($band)
    {
        $this->band = $band;

        return $this;
    }

    /**
     * @return string
     */
    public function getBand()
    {
        return $this->band;
    }

    /**
     * @param int $channelWidth
     *
     * @return DeviceInterface
     */
    public function setChannelWidth(int $channelWidth = null)
    {
        $this->channelWidth = $channelWidth;

        return $this;
    }

    /**
     * @return int
     */
    public function getChannelWidth()
    {
        return $this->channelWidth;
    }

    /**
     * @param string $wirelessProtocol
     *
     * @return DeviceInterface
     */
    public function setWirelessProtocol($wirelessProtocol)
    {
        $this->wirelessProtocol = $wirelessProtocol;

        return $this;
    }

    /**
     * @return string
     */
    public function getWirelessProtocol()
    {
        return $this->wirelessProtocol;
    }

    /**
     * @param string|null $encryptionKeyWpa
     *
     * @return DeviceInterface
     */
    public function setEncryptionKeyWpa($encryptionKeyWpa)
    {
        $this->encryptionKeyWpa = $encryptionKeyWpa;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEncryptionKeyWpa()
    {
        return $this->encryptionKeyWpa;
    }

    /**
     * @param string|null $encryptionKeyWpa2
     *
     * @return DeviceInterface
     */
    public function setEncryptionKeyWpa2($encryptionKeyWpa2)
    {
        $this->encryptionKeyWpa2 = $encryptionKeyWpa2;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEncryptionKeyWpa2()
    {
        return $this->encryptionKeyWpa2;
    }

    /**
     * @return DeviceInterface
     */
    public function setMode(?int $mode)
    {
        $this->mode = $mode;

        return $this;
    }

    public function getMode(): int
    {
        return (int) $this->mode;
    }

    /**
     * @return DeviceInterface
     */
    public function setUnicastCiphers(?int $unicastCiphers)
    {
        $this->unicastCiphers = $unicastCiphers;

        return $this;
    }

    public function getUnicastCiphers(): ?int
    {
        return $this->unicastCiphers;
    }

    /**
     * @return DeviceInterface
     */
    public function setGroupCiphers(?int $groupCiphers)
    {
        $this->groupCiphers = $groupCiphers;

        return $this;
    }

    public function getGroupCiphers(): ?int
    {
        return $this->groupCiphers;
    }

    /**
     * @return DeviceInterface
     */
    public function setEncryptionMode(?int $encryptionMode)
    {
        $this->encryptionMode = $encryptionMode;

        return $this;
    }

    public function getEncryptionMode(): int
    {
        return (int) $this->encryptionMode;
    }

    /**
     * @return DeviceInterface
     */
    public function addServiceDevice(ServiceDevice $serviceDevice)
    {
        $this->serviceDevices[] = $serviceDevice;

        return $this;
    }

    public function removeServiceDevice(ServiceDevice $serviceDevice)
    {
        $this->serviceDevices->removeElement($serviceDevice);
    }

    /**
     * @return Collection|ServiceDevice[]
     */
    public function getServiceDevices()
    {
        return $this->serviceDevices;
    }
}
