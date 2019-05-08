<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nette\Utils\Strings;
use Symfony\Component\Validator\Constraints as Assert;

trait NetworkDeviceTrait
{
    /**
     * @var string
     *
     * @ORM\Column(name="model_name", type="string", length=64, nullable=true)
     * @Assert\Length(max = Device::MODEL_NAME_LENGTH)
     */
    protected $modelName;

    /**
     * @var string
     *
     * @ORM\Column(name="os_version", type="string", length=64, nullable=true)
     * @Assert\Length(max = Device::OS_VERSION_LENGTH)
     */
    protected $osVersion;

    /**
     * @var string
     *
     * @ORM\Column(name="login_username", type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $loginUsername;

    /**
     * @var string|null
     *
     * @ORM\Column(name="login_password", type="text", nullable=true)
     */
    protected $loginPassword;

    /**
     * @var int
     *
     * @ORM\Column(name="ssh_port", type="integer", length=5, nullable=true, options={"default":22})
     * @Assert\Length(max = 5)
     */
    protected $sshPort = BaseDevice::DEFAULT_SSH_PORT;

    /**
     * @var string
     *
     * @ORM\Column(name="backup_hash", type="text", nullable=true)
     */
    protected $backupHash;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="last_backup_timestamp", type="datetime_utc", nullable=true)
     */
    protected $lastBackupTimestamp;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime_utc", nullable=true)
     */
    protected $lastSynchronization;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime_utc", nullable=true)
     */
    protected $lastSuccessfulSynchronization;

    /**
     * @var bool
     *
     * @ORM\Column(name="create_signal_statistics", type="boolean", options={"default":true})
     */
    protected $createSignalStatistics = true;

    /**
     * @var int
     *
     * @ORM\Column(name="qos_enabled", type="integer", options={"default":0})
     * @Assert\NotNull()
     * @Assert\Choice(choices=BaseDevice::POSSIBLE_QOS_TYPES, strict=true)
     */
    protected $qosEnabled = BaseDevice::QOS_DISABLED;

    /**
     * @var bool
     *
     * @ORM\Column(name="qos_synchronized", type="boolean", options={"default":true})
     */
    protected $qosSynchronized = true;

    /**
     * @var int|null
     *
     * @ORM\Column(type="bigint", nullable=true)
     * @Assert\Range(min = 0, max = IpRange::IP_MAX)
     */
    protected $managementIpAddress;

    /**
     * @return $this
     */
    public function setModelName(string $modelName = null)
    {
        // string length checked due to string coming from sync
        $this->modelName = Strings::substring($modelName, 0, Device::MODEL_NAME_LENGTH);

        return $this;
    }

    /**
     * @return string
     */
    public function getModelName()
    {
        return $this->modelName;
    }

    /**
     * @return $this
     */
    public function setOsVersion(string $osVersion = null)
    {
        // string length checked due to string coming from sync
        $this->osVersion = Strings::substring($osVersion, 0, Device::OS_VERSION_LENGTH);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getOsVersion()
    {
        return $this->osVersion;
    }

    /**
     * @return $this
     */
    public function setLoginUsername(string $loginUsername = null)
    {
        $this->loginUsername = $loginUsername;

        return $this;
    }

    /**
     * @return string
     */
    public function getLoginUsername()
    {
        return $this->loginUsername;
    }

    /**
     * @return $this
     */
    public function setLoginPassword(string $loginPassword = null)
    {
        $this->loginPassword = $loginPassword;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLoginPassword()
    {
        return $this->loginPassword;
    }

    /**
     * @return $this
     */
    public function setSshPort(int $sshPort = null)
    {
        $this->sshPort = $sshPort;

        return $this;
    }

    /**
     * @return int
     */
    public function getSshPort()
    {
        return $this->sshPort;
    }

    /**
     * @return $this
     */
    public function setBackupHash(string $backupHash = null)
    {
        $this->backupHash = $backupHash;

        return $this;
    }

    /**
     * @return string
     */
    public function getBackupHash()
    {
        return $this->backupHash;
    }

    /**
     * @return $this
     */
    public function setLastBackupTimestamp(\DateTime $lastBackupTimestamp = null)
    {
        $this->lastBackupTimestamp = $lastBackupTimestamp;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastBackupTimestamp()
    {
        return $this->lastBackupTimestamp;
    }

    /**
     * @return $this
     */
    public function setLastSynchronization(\DateTime $lastSynchronization = null)
    {
        $this->lastSynchronization = $lastSynchronization;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastSynchronization()
    {
        return $this->lastSynchronization;
    }

    public function setLastSuccessfulSynchronization(?\DateTime $lastSuccessfulSynchronization)
    {
        $this->lastSuccessfulSynchronization = $lastSuccessfulSynchronization;
    }

    public function getLastSuccessfulSynchronization(): ?\DateTime
    {
        return $this->lastSuccessfulSynchronization;
    }

    /**
     * @return $this
     */
    public function setCreateSignalStatistics(bool $createSignalStatistics)
    {
        $this->createSignalStatistics = $createSignalStatistics;

        return $this;
    }

    public function getCreateSignalStatistics(): bool
    {
        return $this->createSignalStatistics;
    }

    public function setQosEnabled(?int $qosEnabled): void
    {
        $this->qosEnabled = $qosEnabled;
    }

    public function getQosEnabled(): ?int
    {
        return $this->qosEnabled;
    }

    public function isQosSynchronized(): bool
    {
        return $this->qosSynchronized;
    }

    /**
     * @return $this
     */
    public function setQosSynchronized(bool $qosSynchronized)
    {
        $this->qosSynchronized = $qosSynchronized;

        return $this;
    }

    public function getManagementIpAddress(): ?int
    {
        return $this->managementIpAddress;
    }

    /**
     * @return $this
     */
    public function setManagementIpAddress(?int $managementIpAddress)
    {
        $this->managementIpAddress = $managementIpAddress;

        return $this;
    }
}
