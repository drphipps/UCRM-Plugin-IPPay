<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Sync;

use AppBundle\Entity\BaseDevice;
use AppBundle\Entity\Device as NetworkDevice;
use AppBundle\Entity\DeviceInterface;
use AppBundle\Entity\DeviceInterfaceIp;
use AppBundle\Entity\DeviceLog;
use AppBundle\Entity\ServiceDevice;
use AppBundle\Entity\ServiceDeviceLog;
use AppBundle\Entity\ServiceIp;
use AppBundle\Repository\DeviceInterfaceIpRepository;
use AppBundle\Repository\DeviceInterfaceRepository;
use AppBundle\Repository\ServiceDeviceRepository;
use AppBundle\Repository\ServiceIpRepository;
use AppBundle\Service\Encryption;
use AppBundle\Service\Options;
use AppBundle\Sync\Items\IpAddress;
use AppBundle\Util\File;
use Doctrine\ORM\EntityManager;
use Nette\Utils\Strings;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Translation\TranslatorInterface;

abstract class Device
{
    const BLOCKED_USERS = 'BLOCKED_USERS';

    const INTERNAL_NAME = 'internalName';
    const MAC_ADDRESS = 'macAddress';
    const SSID = 'ssid';
    const ESSID = 'essid';

    const MAX_BACKUP_COUNT = 14;

    /**
     * @var string
     */
    protected $ip;

    /**
     * @var int
     */
    protected $sshPort;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var Encryption
     */
    protected $encryption;

    /**
     * @var BaseDevice|NetworkDevice|ServiceDevice
     */
    protected $device;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var bool
     */
    protected $connected = false;

    /**
     * @var Ssh
     */
    protected $ssh;

    /**
     * @var File
     */
    protected $file;

    /**
     * @var Options
     */
    protected $options;

    /**
     * @var string
     */
    protected $rootDir;

    /**
     * @var DeviceInterfaceRepository
     */
    protected $deviceInterfaceRepository;

    /**
     * @var DeviceInterfaceIpRepository
     */
    protected $deviceInterfaceIpRepository;

    /**
     * @var ServiceDeviceRepository
     */
    protected $serviceDeviceRepository;

    /**
     * @var ServiceIpRepository
     */
    protected $serviceIpRepository;

    /**
     * @var array
     */
    protected $deviceIps = [];

    /**
     * @var \stdClass
     */
    protected $interfaces;

    public function __construct(
        string $rootDir,
        EntityManager $em,
        Encryption $encryption,
        TranslatorInterface $translator,
        Ssh $ssh,
        File $file,
        Options $options
    ) {
        $this->rootDir = $rootDir;
        $this->em = $em;
        $this->encryption = $encryption;
        $this->translator = $translator;
        $this->ssh = $ssh;
        $this->file = $file;
        $this->options = $options;

        $this->deviceInterfaceRepository = $this->em->getRepository(DeviceInterface::class);
        $this->deviceInterfaceIpRepository = $this->em->getRepository(DeviceInterfaceIp::class);
        $this->serviceDeviceRepository = $this->em->getRepository(ServiceDevice::class);
        $this->serviceIpRepository = $this->em->getRepository(ServiceIp::class);

        $this->file->setRootDir($rootDir);
    }

    public function setIp(string $ip): Device
    {
        $this->ip = $ip;

        return $this;
    }

    public function setDevice(BaseDevice $device): Device
    {
        $this->device = $device;

        $this->sshPort = $this->device->getSshPort();
        $this->username = $this->device->getLoginUsername();
        $this->password = (string) $this->device->getLoginPassword();

        return $this;
    }

    public function syncBlockedList(array $localList): bool
    {
        return $this->syncFirewallAddressList(self::BLOCKED_USERS, $localList);
    }

    public function readGeneralInformation(): Device
    {
        return $this;
    }

    public function syncNatRules(string $serverIp, int $serverSuspendPort): Device
    {
        return $this;
    }

    public function syncFilterRules(string $serverIp): Device
    {
        return $this;
    }

    /**
     * Search for unknown service device and connect to deviceInterface.
     */
    public function searchForUnknownConnectedDevices(): Device
    {
        return $this;
    }

    public function saveConfiguration(): Device
    {
        return $this;
    }

    public function saveStatistics(): Device
    {
        return $this;
    }

    public function searchForInterfaces(): Device
    {
        return $this;
    }

    public function searchForWirelessInterfaces(): Device
    {
        return $this;
    }

    public function searchForWirelessSecurity(): Device
    {
        return $this;
    }

    public function searchForInterfaceIpsChangedInterface(): Device
    {
        return $this;
    }

    public function searchForInterfaceIps(): Device
    {
        return $this;
    }

    public function syncQos()
    {
    }

    /**
     * @throws Exceptions\LoginException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException
     */
    public function connect(): Device
    {
        $this->initSsh();
        $this->ssh->setIp($this->ip);
        $this->ssh->setPort($this->sshPort > 0 ? $this->sshPort : ServiceDevice::DEFAULT_SSH_PORT);
        $this->ssh->setUsername($this->username);
        $this->ssh->setPassword($this->encryption->decrypt($this->password));
        $this->ssh->login();
        $this->connected = true;

        return $this;
    }

    public function init(): bool
    {
        return $this->connected;
    }

    public function downloadFileSsh(string $filename): string
    {
        $this->initSsh();

        return $this->ssh->downloadFile($filename);
    }

    public function deleteOldBackups(string $backupDirectory)
    {
        $fs = new Filesystem();
        $finder = new Finder();
        $finder->files()->in($backupDirectory)->sortByModifiedTime();

        $i = 0;
        $count = $finder->count();
        $delete = [];
        foreach ($finder as $file) {
            if ($i < $count - self::MAX_BACKUP_COUNT) {
                ++$i;
                $delete[] = $file->getRealPath();
            }
        }

        if ($delete) {
            $fs->remove($delete);
        }
    }

    public function log(string $message, int $status)
    {
        if ($this->device instanceof NetworkDevice) {
            $log = new DeviceLog();
            $log->setDevice($this->device);
            $log->setScript('SyncDeviceCommand');
        } elseif ($this->device instanceof ServiceDevice) {
            $log = new ServiceDeviceLog();
            $log->setServiceDevice($this->device);
            $log->setScript('SyncServiceDeviceCommand');
        } else {
            return;
        }

        $log->setCreatedDate(new \DateTime());
        $log->setMessage(Strings::fixEncoding($message));
        $log->setStatus($status);

        $this->em->persist($log);
        $this->em->flush($log);
    }

    public function removeEmptyInterfaces(): Device
    {
        $ipCount = 0;
        foreach ($this->device->getNotDeletedInterfaces() as $interface) {
            $ipCount += $interface->getInterfaceIps()->count();
        }

        foreach ($this->device->getNotDeletedInterfaces() as $interface) {
            if ($ipCount === 1 && $interface->getInterfaceIps()->count() > 0) {
                continue;
            }

            if (null === $interface->getInternalId() &&
                null === $interface->getInternalName() &&
                null === $interface->getInternalType() &&
                count($interface->getServiceDevices()) === 0
            ) {
                foreach ($interface->getInterfaceIps() as $ip) {
                    $this->em->remove($ip);
                }

                $interface->setDeletedAt(new \DateTime());
            }
        }

        return $this;
    }

    public function getInterfaces(): \stdClass
    {
        return $this->interfaces;
    }

    protected function getFirewallAddressList(string $name): array
    {
        return [];
    }

    protected function updateFirewallAddressList(string $name, array $toAdd, array $toRemove)
    {
    }

    protected function syncFirewallAddressList(string $name, array $localList): bool
    {
        $remoteList = $this->getFirewallAddressList($name);
        $toAdd = array_diff($localList, $remoteList);
        $toRemove = array_diff($remoteList, $localList);
        if ($toAdd || $toRemove) {
            //do changes
            $this->updateFirewallAddressList($name, $toAdd, $toRemove);
        }

        return true;
    }

    /**
     * @param int|string      $from
     * @param int|string      $to
     * @param array|\stdClass $attributes
     */
    protected function formatLogValues(string $attributeName, $from, $to, $attributes): array
    {
        $from = $from ? $from : 0;
        $to = $to ? $to : 0;

        switch ($attributeName) {
            case 'enabled':
                $from = $from ? 'true' : 'false';
                $to = $to ? 'true' : 'false';
                break;
            case 'encryptionMode':
                if ($from) {
                    $from = DeviceInterface::ENCRYPTION_MODE_TYPES[$from];
                } else {
                    $from = DeviceInterface::ENCRYPTION_MODE_TYPES[DeviceInterface::ENCRYPTION_MODE_NONE];
                }

                if ($to) {
                    $to = DeviceInterface::ENCRYPTION_MODE_TYPES[$to];
                } else {
                    $to = DeviceInterface::ENCRYPTION_MODE_TYPES[DeviceInterface::ENCRYPTION_MODE_NONE];
                }
                break;
            case 'encryptionType':
                $from = DeviceInterface::ENCRYPTION_TYPES[(int) $from];
                $to = DeviceInterface::ENCRYPTION_TYPES[(int) $to];
                break;
            case 'wirelessMode':
            case 'mode':
                if ($from) {
                    $from = DeviceInterface::MODE_TYPES[$from];
                } else {
                    $from = DeviceInterface::MODE_TYPES[DeviceInterface::MODE_UNKNOWN];
                }

                if ($to) {
                    $to = DeviceInterface::MODE_TYPES[$to];
                } else {
                    $to = DeviceInterface::MODE_TYPES[DeviceInterface::MODE_UNKNOWN];
                }
                break;
            case 'frequency':
                $from = $from === 0 ? $this->translator->trans('auto') : $from;
                $to = $to === 0 ? $this->translator->trans('auto') : $to;
                break;
            case 'unicastCiphers':
            case 'groupCiphers':
                if ($from) {
                    $from = DeviceInterface::CIPHER_TYPES[$from];
                } else {
                    $from = DeviceInterface::CIPHER_TYPES[DeviceInterface::CIPHER_NONE];
                }

                if ($to) {
                    $to = DeviceInterface::CIPHER_TYPES[$to];
                } else {
                    $to = DeviceInterface::CIPHER_TYPES[DeviceInterface::CIPHER_NONE];
                }
                break;
            case 'essid':
            case 'opmode':
                $from = $from ? $from : $this->translator->trans('Unknown');
                $to = $to ? $to : $this->translator->trans('Unknown');
                break;
        }

        return [$from, $to];
    }

    /**
     * @param object          $entity
     * @param string          $method
     * @param int|string|bool $from
     * @param int|string|bool $to
     * @param string          $method
     */
    protected function logChangedAttribute(
        $entity,
        string $attributeName,
        $from,
        $to,
        string $method = null
    ) {
        if ((string) $from == (string) $to) {
            return;
        }

        if ($entity instanceof DeviceInterface) {
            $name = $entity->getName();
        } elseif ($entity instanceof DeviceInterfaceIp) {
            $name = $entity->getInterface()->getName();
        } else {
            $name = $this->device->getName();
        }

        $message = $this->translator->trans(
            'Device %attributeName% changed from %from% to %to% on %name%',
            [
                '%attributeName%' => $method ?? $attributeName,
                '%from%' => $from,
                '%to%' => $to,
                '%name%' => $name,
            ]
        );

        $this->log($message, DeviceLog::STATUS_WARNING);
    }

    protected function logAddedNewInterface(string $interfaceName)
    {
        $message = $this->translator->trans(
            'Added new interface %name%',
            [
                '%name%' => $interfaceName,
            ]
        );
        $this->log($message, DeviceLog::STATUS_OK);
    }

    protected function logAddedNewIp(DeviceInterface $deviceInterface, DeviceInterfaceIp $deviceInterfaceIp)
    {
        $message = $this->translator->trans(
            'Added new ip %ip% on %interfaceName%',
            [
                '%ip%' => $deviceInterfaceIp->getIpRange()->getRangeForView(),
                '%interfaceName%' => $deviceInterface->getName(),
            ]
        );
        $this->log($message, DeviceLog::STATUS_OK);
    }

    protected function logRemovedIps(array $removedIps)
    {
        foreach ($removedIps as $ip => $netmask) {
            $message = $this->translator->trans(
                'Removed %ip%/%netmask%',
                [
                    '%ip%' => $ip,
                    '%netmask%' => $netmask,
                ]
            );
            $this->log($message, DeviceLog::STATUS_WARNING);
        }
    }

    protected function logChangedIp(
        DeviceInterface $deviceInterface,
        DeviceInterfaceIp $deviceInterfaceIp,
        string $ipAddress,
        int $netmask
    ) {
        $prevInterface = $deviceInterfaceIp->getInterface();

        $message = $this->translator->trans(
            'Changed %ip%/%netmask% from %prevInterfaceName% to %nextInterfaceName%',
            [
                '%ip%' => $ipAddress,
                '%netmask%' => $netmask,
                '%prevInterfaceName%' => $prevInterface->getInternalName() ?? $prevInterface->getName(),
                '%nextInterfaceName%' => $deviceInterface->getInternalName(),
            ]
        );
        $this->log($message, DeviceLog::STATUS_WARNING);
    }

    /**
     * @param string|int $value
     *
     * @return DeviceInterface|null
     */
    protected function matchInterfaceByField(string $field, $value)
    {
        $pa = PropertyAccess::createPropertyAccessor();

        foreach ($this->device->getNotDeletedInterfaces() as $interface) {
            if ($pa->getValue($interface, $field) === $value) {
                return $interface;
            }
        }

        return null;
    }

    /**
     * @return DeviceInterface|null
     */
    protected function matchInterfaceByMacAddress(string $macAddress)
    {
        /** @var DeviceInterface $interface */
        foreach ($this->device->getNotDeletedInterfaces() as $interface) {
            if ($interface->getType() === DeviceInterface::TYPE_BRIDGE) {
                continue;
            }

            if ($macAddress === $interface->getMacAddress()) {
                return $interface;
            }
        }

        return null;
    }

    /**
     * @return DeviceInterface|null
     */
    protected function findDeviceInterfaceByIpAddress(array $routerAddressList, string $interfaceName)
    {
        if (empty($this->deviceIps)) {
            /** @var DeviceInterface $interface */
            foreach ($this->device->getNotDeletedInterfaces() as $interface) {
                $this->deviceIps[$interface->getId()] = [];
                foreach ($interface->getInterfaceIps() as $ip) {
                    $ipAddress = new IpAddress();
                    $ipAddress->ipInt = $ip->getIpRange()->getIpAddress();
                    $ipAddress->ip = long2ip($ipAddress->ipInt);
                    $ipAddress->netmask = $ip->getIpRange()->getNetmask() ?? 32;

                    $this->deviceIps[$interface->getId()][$ipAddress->ipInt] = $ipAddress;
                }

                ksort($this->deviceIps[$interface->getId()]);
            }
        }

        if (! array_key_exists($interfaceName, $routerAddressList)) {
            return null;
        }

        foreach ($this->deviceIps as $deviceInterfaceId => $deviceInterfaceAddressList) {
            $needleAddressList = $routerAddressList[$interfaceName];

            if (empty($deviceInterfaceAddressList) || empty($needleAddressList)) {
                continue;
            }

            ksort($needleAddressList);
            if (empty(array_diff($deviceInterfaceAddressList, $needleAddressList))) {
                return $this->matchInterfaceByField('id', $deviceInterfaceId);
            }
        }

        return null;
    }

    /**
     * @param DeviceInterface $deviceInterface
     */
    protected function changeIpOnInterface(
        int $ipAddress,
        int $netmask,
        string $internalId,
        string $interfaceName,
        DeviceInterface $deviceInterface = null
    ) {
        $deviceInterface = $deviceInterface ?? $this->matchInterfaceByField(self::INTERNAL_NAME, $interfaceName);
        $deviceInterfaceIp = $this->deviceInterfaceIpRepository->findByIpAddress($ipAddress, $netmask);

        if (! $deviceInterface || ! $deviceInterfaceIp) {
            return;
        }

        $prevInterface = $deviceInterfaceIp->getInterface();
        if (
            $prevInterface->getInternalName() !== $deviceInterface->getInternalName() &&
            $prevInterface->getDevice()->getId() === $deviceInterface->getDevice()->getId()
        ) {
            $this->logChangedIp($deviceInterface, $deviceInterfaceIp, long2ip($ipAddress), $netmask);
            $deviceInterfaceIp->setInterface($deviceInterface);
            $deviceInterfaceIp->setInternalId($internalId);
        }
    }

    protected function setInterfaceIpAccessible(DeviceInterfaceIp $interfaceIp)
    {
        if ($this->device instanceof NetworkDevice) {
            $searchIp = $this->device->getSearchIp();
            $interfaceIp->setIsAccessible(
                null !== $searchIp && $searchIp->isIpEqualTo($interfaceIp)
            );
        } else {
            $interfaceIp->setIsAccessible(false);
        }
    }

    /**
     * @return array [bool $isKnown, ServiceDevice|null $serviceDevice]
     */
    protected function findUnknownDevice(string $macAddress, ?int $lastIp): array
    {
        // Check service devices by MAC
        list($isKnown, $unknownServiceDevice) = $this->serviceDeviceRepository->findUnknownByMac($macAddress);
        if ($isKnown) {
            return [true, null];
        }

        // Check service devices by IP
        if (null !== $lastIp) {
            $serviceDeviceByIp = $this->serviceIpRepository->findOneBy(
                [
                    'ipRange.ipAddress' => $lastIp,
                ]
            );

            if ($serviceDeviceByIp && null !== $serviceDeviceByIp->getServiceDevice()->getService()) {
                return [true, null];
            }
        }

        // Check network devices by MAC
        if (null !== $this->deviceInterfaceRepository->findByMac($macAddress)) {
            return [true, null];
        }

        // Check network devices by IP
        if (null !== $lastIp && null !== $this->deviceInterfaceIpRepository->findByIpAddress($lastIp)) {
            return [true, null];
        }

        return [false, $unknownServiceDevice];
    }

    protected function sanitizeSSID(?string $ssid): ?string
    {
        return null !== $ssid
            ? Strings::truncate(html_entity_decode($ssid, ENT_QUOTES | ENT_HTML401), 32, '')
            : null;
    }

    private function initSsh()
    {
        if (null === $this->ssh) {
            $this->ssh = new Ssh();
        }
    }
}
