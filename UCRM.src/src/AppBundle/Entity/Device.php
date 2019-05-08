<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use AppBundle\Component\Validator\Constraints as CustomAssert;
use AppBundle\Sync\AirOs;
use AppBundle\Sync\EdgeOs;
use AppBundle\Sync\RouterOs;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\DeviceRepository")
 * @ORM\Table(
 *     indexes={
 *         @ORM\Index(columns={"deleted_at"}),
 *         @ORM\Index(columns={"name"}),
 *     }
 * )
 */
class Device extends BaseDevice implements LoggableInterface, ParentLoggableInterface, SoftDeleteLoggableInterface
{
    use SoftDeleteableTrait;

    const BACKUP_DIRECTORY = 'device';

    const NETFLOW_VERSION_V5 = 5;
    const NETFLOW_VERSION_V9 = 9;

    const NETFLOW_VERSIONS = [
        self::NETFLOW_VERSION_V5 => 'NetFlow v5',
        self::NETFLOW_VERSION_V9 => 'NetFlow v9',
    ];

    const OS_VERSION_LENGTH = 64;
    const MODEL_NAME_LENGTH = 64;

    /**
     * @var int
     *
     * @ORM\Column(name="device_id", type="integer")
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
     * @var Vendor
     *
     * @ORM\ManyToOne(targetEntity="Vendor", inversedBy="devices")
     * @ORM\JoinColumn(name="vendor_id", referencedColumnName="vendor_id", nullable=false)
     */
    protected $vendor;

    /**
     * @var string
     *
     * @ORM\Column(name="snmp_community", type="string", length=32, nullable=true)
     * @Assert\Length(max = 32)
     */
    protected $snmpCommunity;

    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="text", nullable=true)
     */
    protected $notes;

    /**
     * @var Site
     *
     * @Assert\NotNull
     * @ORM\ManyToOne(targetEntity="Site", inversedBy="devices", fetch="EAGER")
     * @ORM\JoinColumn(name="site_id", referencedColumnName="site_id", nullable=false)
     */
    protected $site;

    /**
     * @var Collection|DeviceInterface[]
     *
     * @ORM\OneToMany(targetEntity="DeviceInterface", mappedBy="device")
     */
    protected $interfaces;

    /**
     * @var bool
     *
     * @ORM\Column(name="synchronized", type="boolean", options={"default":true})
     */
    protected $synchronized = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_gateway", type="boolean", options={"default":false})
     */
    protected $isGateway = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_suspend_enabled", type="boolean", options={"default":false})
     */
    protected $isSuspendEnabled = false;

    /**
     * @var Collection|Device[]
     *
     * @ORM\ManyToMany(targetEntity="Device", inversedBy="children", cascade={"persist"})
     * @ORM\JoinTable(
     *     name="device_relations",
     *     joinColumns={@ORM\JoinColumn(name="child_id", referencedColumnName="device_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="parent_id", referencedColumnName="device_id", onDelete="CASCADE")}
     * )
     */
    protected $parents;

    /**
     * @var Collection|Device[]
     *
     * @ORM\ManyToMany(targetEntity="Device", mappedBy="parents")
     */
    protected $children;

    /**
     * @var Collection|DeviceOutage[]
     *
     * @ORM\OneToMany(targetEntity="DeviceOutage", mappedBy="device")
     */
    protected $outages;

    /**
     * @var bool
     *
     * @ORM\Column(name="send_ping_notifications", type="boolean", options={"default":true})
     */
    protected $sendPingNotifications = true;

    /**
     * @var Collection|Device[]
     *
     * @CustomAssert\QosCycle()
     * @ORM\ManyToMany(targetEntity="Device", inversedBy="qosDeviceChildren", cascade={"persist"})
     * @ORM\JoinTable(
     *     name="device_qos",
     *     joinColumns={@ORM\JoinColumn(name="device_id", referencedColumnName="device_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="parent_device_id", referencedColumnName="device_id", onDelete="CASCADE")}
     * )
     * @Assert\All({
     *     @Assert\Expression(
     *         expression="value.getVendor().getId() in [constant('\\AppBundle\\Entity\\Vendor::EDGE_OS')]",
     *         message="QoS is supported only for EdgeOS devices."
     *     )
     * })
     *
     * @todo Modify the expression constraint when UCRM-62 is implemented
     */
    protected $qosDevices;

    /**
     * @var Collection|Device[]
     *
     * @ORM\ManyToMany(targetEntity="Device", mappedBy="qosDevices")
     */
    protected $qosDeviceChildren;

    /**
     * @var Collection|ServiceDevice[]
     *
     * @ORM\ManyToMany(targetEntity="ServiceDevice", mappedBy="qosDevices")
     */
    protected $qosServiceDeviceChildren;

    /**
     * @var Collection|WirelessStatisticsShortTerm[]
     *
     * @ORM\OneToMany(targetEntity="WirelessStatisticsShortTerm", mappedBy="device")
     */
    protected $wirelessStatisticsShortTerm;

    /**
     * @var Collection|WirelessStatisticsLongTerm[]
     *
     * @ORM\OneToMany(targetEntity="WirelessStatisticsLongTerm", mappedBy="device")
     */
    protected $wirelessStatisticsLongTerm;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": true})
     */
    protected $netFlowSynchronized = true;

    /**
     * @var int|null
     *
     * @ORM\Column(type="smallint", nullable=true)
     */
    protected $netFlowActiveVersion;

    /**
     * @var int|null
     *
     * @ORM\Column(type="smallint", nullable=true)
     */
    protected $netFlowPendingVersion;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $netFlowLog;

    /**
     * @var DeviceIp|null
     *
     * @ORM\OneToOne(targetEntity="DeviceIp", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="search_ip", referencedColumnName="ip_id", onDelete="SET NULL")
     */
    protected $searchIp;

    /**
     * @var float|null
     *
     * @ORM\Column(type="float", nullable=true)
     */
    protected $bandwidth;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->interfaces = new ArrayCollection();
        $this->outages = new ArrayCollection();
        $this->parents = new ArrayCollection();
        $this->qosDeviceChildren = new ArrayCollection();
        $this->qosDevices = new ArrayCollection();
        $this->qosServiceDeviceChildren = new ArrayCollection();
        $this->wirelessStatisticsLongTerm = new ArrayCollection();
        $this->wirelessStatisticsShortTerm = new ArrayCollection();
    }

    /**
     * Required for accessing parent QoS devices of a changed Device entity
     * and removing this Device from QoS rules on them.
     */
    public function __clone()
    {
        $this->qosDevices = clone $this->qosDevices;
    }

    public function setId(int $id): Device
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setVendor(Vendor $vendor): Device
    {
        $this->vendor = $vendor;

        return $this;
    }

    /**
     * @return Vendor
     */
    public function getVendor()
    {
        return $this->vendor;
    }

    public function addInterface(DeviceInterface $interface)
    {
        $this->interfaces[] = $interface;
    }

    public function removeInterface(DeviceInterface $interface)
    {
        $this->interfaces->removeElement($interface);
    }

    /**
     * @deprecated use getNotDeletedInterfaces instead
     *
     * @return Collection|DeviceInterface[]
     */
    public function getInterfaces()
    {
        return $this->interfaces;
    }

    /**
     * @return Collection|DeviceInterface[]
     */
    public function getNotDeletedInterfaces()
    {
        return $this->interfaces->matching(
            Criteria::create()->where(Criteria::expr()->isNull('deletedAt'))
        );
    }

    /**
     * @return Site
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * @return Device
     */
    public function setSite(Site $site)
    {
        $this->site = $site;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return Device
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
     * @param string|null $snmpCommunity
     *
     * @return Device
     */
    public function setSnmpCommunity($snmpCommunity)
    {
        $this->snmpCommunity = $snmpCommunity;

        return $this;
    }

    /**
     * @return string
     */
    public function getSnmpCommunity()
    {
        return $this->snmpCommunity;
    }

    /**
     * @param string $notes
     *
     * @return Device
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
     * @return Device
     */
    public function setSynchronized(bool $synchronized)
    {
        $this->synchronized = $synchronized;

        return $this;
    }

    public function getSynchronized(): bool
    {
        return $this->synchronized;
    }

    /**
     * @return Device
     */
    public function addParent(Device $parent)
    {
        $this->parents[] = $parent;

        return $this;
    }

    public function removeParent(Device $parent)
    {
        $this->parents->removeElement($parent);
    }

    /**
     * @return Collection|Device[]
     */
    public function getParents()
    {
        return $this->parents;
    }

    /**
     * @return Device
     */
    public function addChild(Device $child)
    {
        $this->children[] = $child;

        return $this;
    }

    public function removeChild(Device $child)
    {
        $this->children->removeElement($child);
    }

    /**
     * @return Collection|Device[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return Device
     */
    public function addOutage(DeviceOutage $outage)
    {
        $this->outages[] = $outage;

        return $this;
    }

    public function removeOutage(DeviceOutage $outage)
    {
        $this->outages->removeElement($outage);
    }

    /**
     * @param int $limit
     *
     * @return Collection|DeviceOutage[]
     */
    public function getOutages(int $limit = null)
    {
        $sort = Criteria::create()
            ->orderBy(
                [
                    'outageStart' => Criteria::DESC,
                ]
            );

        if ($limit) {
            $sort->setMaxResults($limit);
        }

        return $this->outages->matching($sort);
    }

    /**
     * @return DeviceOutage|null
     */
    public function getLastOutage()
    {
        $criteria = Criteria::create()
            ->orderBy(
                [
                    'outageStart' => Criteria::DESC,
                ]
            )
            ->setMaxResults(1);

        return $this->outages->matching($criteria)->first() ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDeleteMessage()
    {
        $message['logMsg'] = [
            'message' => 'Device %s deleted',
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
            'message' => 'Device %s archived',
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
            'message' => 'Device %s restored',
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
            'message' => 'Device %s added',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogIgnoredColumns()
    {
        return [
            'loginPassword',
            'synchronized',
            'status',
            'pingErrorCount',
            'pingNotificationSent',
            'lastSynchronization',
            'lastBackupTimestamp',
        ];
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
        return $this->getSite();
    }

    /**
     * {@inheritdoc}
     */
    public function getLogParentEntity()
    {
        return $this->getSite();
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
     /**
     *
     * @return Device
     */
    public function setIsGateway(bool $isGateway)
    {
        $this->isGateway = $isGateway;

        return $this;
    }

    public function isGateway(): bool
    {
        return $this->isGateway;
    }

    public function isSendPingNotifications(): bool
    {
        return $this->sendPingNotifications;
    }

    /**
     * @return $this
     */
    public function setSendPingNotifications(bool $sendPingNotifications)
    {
        $this->sendPingNotifications = $sendPingNotifications;

        return $this;
    }

    /**
     * @return Device
     */
    public function setIsSuspendEnabled(bool $isSuspendEnabled)
    {
        $this->isSuspendEnabled = $isSuspendEnabled;

        return $this;
    }

    public function isSuspendEnabled(): bool
    {
        return $this->isSuspendEnabled;
    }

    /**
     * @return Device
     */
    public function addQosDevice(Device $qosDevice)
    {
        $this->qosDevices->add($qosDevice);

        return $this;
    }

    public function removeQosDevice(Device $qosDevice)
    {
        $this->qosDevices->removeElement($qosDevice);
    }

    /**
     * @return Collection|Device[]
     */
    public function getQosDevices()
    {
        return $this->qosDevices;
    }

    /**
     * @return Device
     */
    public function addQosDeviceChild(Device $qosDeviceChild)
    {
        $this->qosDeviceChildren[] = $qosDeviceChild;

        return $this;
    }

    public function removeQosDeviceChild(Device $qosDeviceChild)
    {
        $this->qosDeviceChildren->removeElement($qosDeviceChild);
    }

    /**
     * @return Collection|Device[]
     */
    public function getQosDeviceChildren()
    {
        return $this->qosDeviceChildren;
    }

    /**
     * @return Device
     */
    public function addQosServiceDeviceChild(ServiceDevice $qosServiceDeviceChild)
    {
        $this->qosServiceDeviceChildren[] = $qosServiceDeviceChild;

        return $this;
    }

    public function removeQosServiceDeviceChild(ServiceDevice $qosServiceDeviceChild)
    {
        $this->qosServiceDeviceChildren->removeElement($qosServiceDeviceChild);
    }

    /**
     * @return Collection|ServiceDevice[]
     */
    public function getQosServiceDeviceChildren()
    {
        return $this->qosServiceDeviceChildren;
    }

    public function getNameWithSite(): string
    {
        return sprintf(
            '%s - %s',
            $this->getSite()->getName(),
            $this->getName()
        );
    }

    /**
     * @return Device
     */
    public function addWirelessStatisticsShortTerm(WirelessStatisticsShortTerm $wirelessStatisticsShortTerm)
    {
        $this->wirelessStatisticsShortTerm[] = $wirelessStatisticsShortTerm;

        return $this;
    }

    public function removeWirelessStatisticsShortTerm(WirelessStatisticsShortTerm $wirelessStatisticsShortTerm)
    {
        $this->wirelessStatisticsShortTerm->removeElement($wirelessStatisticsShortTerm);
    }

    /**
     * @return Collection|WirelessStatisticsShortTerm[]
     */
    public function getWirelessStatisticsShortTerm()
    {
        return $this->wirelessStatisticsShortTerm;
    }

    /**
     * @return Device
     */
    public function addWirelessStatisticsLongTerm(WirelessStatisticsLongTerm $wirelessStatisticsLongTerm)
    {
        $this->wirelessStatisticsLongTerm[] = $wirelessStatisticsLongTerm;

        return $this;
    }

    public function removeWirelessStatisticsLongTerm(WirelessStatisticsLongTerm $wirelessStatisticsLongTerm)
    {
        $this->wirelessStatisticsLongTerm->removeElement($wirelessStatisticsLongTerm);
    }

    /**
     * @return Collection|WirelessStatisticsLongTerm[]
     */
    public function getWirelessStatisticsLongTerm()
    {
        return $this->wirelessStatisticsLongTerm;
    }

    public function isNetFlowSynchronized(): bool
    {
        return $this->netFlowSynchronized;
    }

    public function setNetFlowSynchronized(bool $netFlowSynchronized): Device
    {
        $this->netFlowSynchronized = $netFlowSynchronized;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getNetFlowActiveVersion()
    {
        return $this->netFlowActiveVersion;
    }

    public function setNetFlowActiveVersion(int $netFlowActiveVersion = null): Device
    {
        $this->netFlowActiveVersion = $netFlowActiveVersion;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getNetFlowPendingVersion()
    {
        return $this->netFlowPendingVersion;
    }

    public function setNetFlowPendingVersion(int $netFlowPendingVersion = null): Device
    {
        $this->netFlowPendingVersion = $netFlowPendingVersion;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getNetFlowLog()
    {
        return $this->netFlowLog;
    }

    public function setNetFlowLog(string $netFlowLog = null): Device
    {
        $this->netFlowLog = $netFlowLog;

        return $this;
    }

    /**
     * @return DeviceIp|null
     */
    public function getSearchIp()
    {
        return $this->searchIp;
    }

    public function setSearchIp(DeviceIp $searchIp = null): Device
    {
        $this->searchIp = $searchIp;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getBandwidth()
    {
        return $this->bandwidth;
    }

    public function setBandwidth(float $bandwidth = null): Device
    {
        $this->bandwidth = $bandwidth;

        return $this;
    }

    /**
     * @throws MissingDriverException
     */
    public function getDriverClassName(): string
    {
        switch ($this->getVendor()->getId()) {
            case Vendor::EDGE_OS:
                return EdgeOs::class;
            case Vendor::AIR_OS:
                return AirOs::class;
            case Vendor::ROUTER_OS:
                return RouterOs::class;
            default:
                throw new MissingDriverException(
                    sprintf('Missing driver for vendor %s.', $this->getVendor()->getName())
                );
        }
    }

    public function getDeviceIps(): array
    {
        $ipsLastSuccessfully = [];
        $ipsLastNotSuccessfully = [];

        if (null !== $this->searchIp) {
            $this->processDeviceIp($this->searchIp, $ipsLastSuccessfully, $ipsLastNotSuccessfully);
        }

        foreach ($this->getNotDeletedInterfaces() as $interface) {
            foreach ($interface->getInterfaceIps() as $ip) {
                if ($ip->getIsAccessible()) {
                    $this->processDeviceIp($ip, $ipsLastSuccessfully, $ipsLastNotSuccessfully);
                }
            }
        }

        return array_merge($ipsLastSuccessfully, $ipsLastNotSuccessfully);
    }

    public function getQosAttributes(): array
    {
        return [
            $this->loginUsername,
            $this->loginPassword,
            $this->vendor ? $this->vendor->getId() : null,
            $this->sshPort,
            $this->qosEnabled,
            $this->qosDevices->map(
                function (Device $device) {
                    return $device->getId();
                }
            )->toArray(),
            $this->isGateway,
            $this->bandwidth,
        ];
    }

    public function getBackupDirectory(): string
    {
        return self::BACKUP_DIRECTORY;
    }
}
