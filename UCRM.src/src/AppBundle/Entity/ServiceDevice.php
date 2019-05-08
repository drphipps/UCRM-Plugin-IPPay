<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use AppBundle\Component\Validator\Constraints as CustomAssert;
use AppBundle\Sync\AirOsServiceDevice;
use AppBundle\Sync\RouterOsServiceDevice;
use AppBundle\Util\Mac;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(
 *     indexes={
 *         @ORM\Index(name="service_device_mac_address_idx", columns={"mac_address"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ServiceDeviceRepository")
 */
class ServiceDevice extends BaseDevice
{
    const AIROS_MAC_ADDRESS_INTERFACE_NAME = ['wifi0', 'ath0'];
    const BACKUP_DIRECTORY = 'service_device';

    const VALIDATION_GROUP_SERVICE_DEVICE = 'ServiceDevice';
    const VALIDATION_GROUP_API = 'Api';

    /**
     * @var int
     *
     * @ORM\Column(name="service_device_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var DeviceInterface
     *
     * @ORM\ManyToOne(targetEntity="DeviceInterface", inversedBy="serviceDevices")
     * @ORM\JoinColumn(name="interface_id", referencedColumnName="interface_id", nullable=false)
     * @Assert\NotNull()
     */
    protected $interface;

    /**
     * This column is left nullable because of device sync.
     *
     * @var Vendor
     *
     * @ORM\ManyToOne(targetEntity="Vendor", inversedBy="serviceDevices")
     * @ORM\JoinColumn(name="vendor_id", referencedColumnName="vendor_id", nullable=true)
     * @Assert\NotNull(groups={ServiceDevice::VALIDATION_GROUP_API})
     */
    protected $vendor;

    /**
     * @var string
     *
     * @ORM\Column(name="mac_address", type="string", length=17, nullable=true)
     * @CustomAssert\Mac()
     */
    protected $macAddress;

    /**
     * @var string
     *
     * @ORM\Column(name="rx_rate", type="string", length=32, nullable=true)
     * @Assert\Length(max = 32)
     */
    protected $rxRate;

    /**
     * @var string
     *
     * @ORM\Column(name="tx_rate", type="string", length=32, nullable=true)
     * @Assert\Length(max = 32)
     */
    protected $txRate;

    /**
     * @var string
     *
     * @ORM\Column(name="uptime", type="string", length=32, nullable=true)
     * @Assert\Length(max = 32)
     */
    protected $uptime;

    /**
     * @var string
     *
     * @ORM\Column(name="last_activity", type="string", length=32, nullable=true)
     * @Assert\Length(max = 32)
     */
    protected $lastActivity;

    /**
     * @var string
     *
     * @ORM\Column(name="signal_strength", type="string", length=32, nullable=true)
     * @Assert\Length(max = 32)
     */
    protected $signalStrength;

    /**
     * @var string
     *
     * @ORM\Column(name="signal_to_noise", type="string", length=32, nullable=true)
     * @Assert\Length(max = 32)
     */
    protected $signalToNoise;

    /**
     * @var int|null
     *
     * @ORM\Column(name="tx_ccq", type="integer", length=3, nullable=true)
     * @Assert\Length(max = 3)
     */
    protected $txCcq;

    /**
     * @var string|null
     *
     * @ORM\Column(name="last_ip", type="string", length=32, nullable=true)
     * @Assert\Length(max = 32)
     */
    protected $lastIp;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="first_seen", type="datetime_utc", nullable=true)
     */
    protected $firstSeen;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_seen", type="datetime_utc", nullable=true)
     */
    protected $lastSeen;

    /**
     * @var Collection|ServiceIp[]
     *
     * @ORM\OneToMany(targetEntity="ServiceIp", mappedBy="serviceDevice", cascade={"remove", "persist"}, orphanRemoval=true)
     * @Assert\Valid()
     * @Assert\Count(min=1, minMessage="This value should not be blank.", groups={ServiceDevice::VALIDATION_GROUP_API})
     */
    protected $serviceIps;

    /**
     * @var Service|null
     *
     * @ORM\ManyToOne(targetEntity="Service", inversedBy="serviceDevices")
     * @ORM\JoinColumn(name="service_id", referencedColumnName="service_id", nullable=true)
     */
    protected $service;

    /**
     * @var ArrayCollection|ServiceDeviceOutage[]
     *
     * @ORM\OneToMany(targetEntity="ServiceDeviceOutage", mappedBy="serviceDevice")
     * @ORM\JoinColumn(name="service_device_id", referencedColumnName="service_device_id")
     */
    protected $outages;

    /**
     * @var Collection|WirelessStatisticsServiceShortTerm[]
     *
     * @ORM\OneToMany(targetEntity="WirelessStatisticsServiceShortTerm", mappedBy="serviceDevice", cascade={"remove", "persist"})
     * @ORM\JoinColumn(name="service_device_id", referencedColumnName="service_device_id")
     */
    protected $wirelessStatisticsServiceShortTerm;

    /**
     * @var Collection|WirelessStatisticsServiceLongTerm[]
     *
     * @ORM\OneToMany(targetEntity="WirelessStatisticsServiceLongTerm", mappedBy="serviceDevice", cascade={"remove", "persist"})
     * @ORM\JoinColumn(name="service_device_id", referencedColumnName="service_device_id")
     */
    protected $wirelessStatisticsServiceLongTerm;

    /**
     * @var bool
     *
     * @ORM\Column(name="send_ping_notifications", type="boolean", options={"default":false})
     */
    protected $sendPingNotifications = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="create_ping_statistics", type="boolean", options={"default":true})
     */
    protected $createPingStatistics = true;

    /**
     * @var Collection|Device[]
     *
     * @ORM\ManyToMany(targetEntity="Device", inversedBy="qosServiceDeviceChildren", cascade={"persist"})
     * @ORM\JoinTable(
     *     name="service_device_qos",
     *     joinColumns={@ORM\JoinColumn(name="service_device_id", referencedColumnName="service_device_id", onDelete="CASCADE")},
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
     * @var Collection|ServiceIp[]|null
     */
    protected $qosServiceIps;

    public function __construct()
    {
        $this->qosDevices = new ArrayCollection();
        $this->serviceIps = new ArrayCollection();
    }

    /**
     * Required for accessing parent QoS devices of a changed ServiceDevice entity
     * and removing this ServiceDevice from QoS rules on them.
     */
    public function __clone()
    {
        $this->qosDevices = clone $this->qosDevices;
        $this->serviceIps = clone $this->serviceIps;
        $this->qosServiceIps = $this->serviceIps->map(
            function (ServiceIp $serviceIp) {
                return clone $serviceIp;
            }
        );
    }

    public function setId(int $id): ServiceDevice
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

    public function setVendor(Vendor $vendor): ServiceDevice
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

    /**
     * @param string $macAddress
     *
     * @return ServiceDevice
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
     * @return ServiceDevice
     */
    public function setRxRate(string $rxRate)
    {
        $this->rxRate = $rxRate;

        return $this;
    }

    /**
     * @return string
     */
    public function getRxRate()
    {
        return $this->rxRate;
    }

    /**
     * @return ServiceDevice
     */
    public function setTxRate(string $txRate)
    {
        $this->txRate = $txRate;

        return $this;
    }

    /**
     * @return string
     */
    public function getTxRate()
    {
        return $this->txRate;
    }

    /**
     * @return ServiceDevice
     */
    public function setUptime(string $uptime)
    {
        $this->uptime = $uptime;

        return $this;
    }

    /**
     * @return string
     */
    public function getUptime()
    {
        return $this->uptime;
    }

    /**
     * @return ServiceDevice
     */
    public function setLastActivity(string $lastActivity)
    {
        $this->lastActivity = $lastActivity;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastActivity()
    {
        return $this->lastActivity;
    }

    /**
     * @return ServiceDevice
     */
    public function setSignalStrength(string $signalStrength)
    {
        $this->signalStrength = $signalStrength;

        return $this;
    }

    /**
     * @return string
     */
    public function getSignalStrength()
    {
        return $this->signalStrength;
    }

    /**
     * @return ServiceDevice
     */
    public function setSignalToNoise(string $signalToNoise)
    {
        $this->signalToNoise = $signalToNoise;

        return $this;
    }

    /**
     * @return string
     */
    public function getSignalToNoise()
    {
        return $this->signalToNoise;
    }

    /**
     * @return ServiceDevice
     */
    public function setTxCcq(int $txCcq = null)
    {
        $this->txCcq = $txCcq;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getTxCcq()
    {
        return $this->txCcq;
    }

    public function setLastIp(?string $lastIp): ServiceDevice
    {
        $this->lastIp = $lastIp;

        return $this;
    }

    public function getLastIp(): ?string
    {
        return $this->lastIp;
    }

    /**
     * @return ServiceDevice
     */
    public function setInterface(DeviceInterface $interface = null)
    {
        $this->interface = $interface;

        return $this;
    }

    /**
     * @return DeviceInterface|null
     */
    public function getInterface()
    {
        return $this->interface;
    }

    /**
     * @return ServiceDevice
     */
    public function setFirstSeen(\DateTime $firstSeen)
    {
        $this->firstSeen = $firstSeen;

        return $this;
    }

    public function getFirstSeen(): \DateTime
    {
        return $this->firstSeen;
    }

    /**
     * @return ServiceDevice
     */
    public function setLastSeen(\DateTime $lastSeen)
    {
        $this->lastSeen = $lastSeen;

        return $this;
    }

    public function getLastSeen(): \DateTime
    {
        return $this->lastSeen;
    }

    /**
     * @return ServiceDevice
     */
    public function addServiceIp(ServiceIp $serviceIp)
    {
        $this->serviceIps[] = $serviceIp;

        return $this;
    }

    public function removeServiceIp(ServiceIp $serviceIp)
    {
        $this->serviceIps->removeElement($serviceIp);
    }

    /**
     * @return Collection|ServiceIp[]
     */
    public function getServiceIps()
    {
        return $this->serviceIps;
    }

    /**
     * @return ServiceDevice
     */
    public function setService(?Service $service = null)
    {
        $this->service = $service;

        return $this;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function getNameForView(): string
    {
        if (! $this->serviceIps->isEmpty()) {
            return $this->serviceIps->first()->getIpRange()->getRangeForView();
        }

        if (null !== $this->managementIpAddress) {
            return long2ip($this->managementIpAddress);
        }

        return $this->macAddress ?: (string) $this->id;
    }

    /**
     * @return ServiceDevice
     */
    public function addOutage(ServiceDeviceOutage $outage)
    {
        $this->outages[] = $outage;

        return $this;
    }

    public function removeOutage(ServiceDeviceOutage $outage)
    {
        $this->outages->removeElement($outage);
    }

    /**
     * @param int $limit
     *
     * @return Collection|ServiceDeviceOutage[]
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
     * @return ServiceDeviceOutage|null
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

    public function isCreatePingStatistics(): bool
    {
        return $this->createPingStatistics;
    }

    /**
     * @return $this
     */
    public function setCreatePingStatistics(bool $createPingStatistics)
    {
        $this->createPingStatistics = $createPingStatistics;

        return $this;
    }

    /**
     * @return ServiceDevice
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
     * @throws MissingDriverException
     */
    public function getDriverClassName(): string
    {
        switch ($this->getVendor()->getId()) {
            case Vendor::AIR_OS:
                return AirOsServiceDevice::class;
            case Vendor::ROUTER_OS:
                return RouterOsServiceDevice::class;
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

        foreach ($this->getServiceIps() as $ip) {
            $this->processDeviceIp($ip, $ipsLastSuccessfully, $ipsLastNotSuccessfully);
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
            $this->getQosServiceIps(),
        ];
    }

    public function getQosServiceIps(): array
    {
        return ($this->qosServiceIps ?? $this->serviceIps)
            ->map(
                function (ServiceIp $serviceIp) {
                    return $serviceIp->getIpRange()->getRangeForView();
                }
            )
            ->toArray();
    }

    public function getBackupDirectory(): string
    {
        return self::BACKUP_DIRECTORY;
    }
}
