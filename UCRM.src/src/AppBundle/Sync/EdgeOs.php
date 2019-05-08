<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Sync;

use AppBundle\Component\QoS\AddressListProvider;
use AppBundle\Component\QoS\CommandLogger;
use AppBundle\Component\QoS\EdgeOs\CommandBuilder;
use AppBundle\Component\QoS\EdgeOs\Config;
use AppBundle\Entity\BaseDevice;
use AppBundle\Entity\Device as NetworkDevice;
use AppBundle\Entity\DeviceInterface;
use AppBundle\Entity\DeviceInterfaceIp;
use AppBundle\Entity\DeviceLog;
use AppBundle\Entity\Option;
use AppBundle\Entity\Vendor;
use AppBundle\Service\Encryption;
use AppBundle\Service\Options;
use AppBundle\Sync\Exceptions\EmptyResponseException;
use AppBundle\Sync\Exceptions\QoSSyncNotSupportedException;
use AppBundle\Sync\Exceptions\RemoteCommandException;
use AppBundle\Sync\Items\IpAddress;
use AppBundle\Sync\Items\NetworkInterface;
use AppBundle\Util\File;
use AppBundle\Util\Mac;
use AppBundle\Util\VyattaParser;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Nette\Utils\Strings;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Translation\TranslatorInterface;

class EdgeOs extends UbntDevice
{
    const CMD_RUN = '/opt/vyatta/sbin/vyatta-cfg-cmd-wrapper';
    const BACKUP_FILEPATH = '/tmp/ucrm_backup.tar.gz';

    const SET_DEFAULT_NETMASK = false;

    const UNMANAGED_INTERFACES = [
        'lo',
    ];

    const QOS_MINIMUM_OS_VERSION = '1.8.5';
    const QOS_IP_COUNT_UNLIMITED_VERSION = '1.10.0';

    const SOCKET_FILENAME = 'EdgeOsSocket.php';
    const SOCKET_LOCAL_PATH = '/internal/edgeos_socket'; // relative to app dir
    const SOCKET_REMOTE_PATH = '/tmp';

    const FORMAT_CIDR_PARTIAL = '(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)[\s]{0,}\/[\s]{0,}(?:(?:3[0-2]|2[0-9]|1[0-9]|[0-9])){1}';

    /**
     * @var AddressListProvider
     */
    private $addressListProvider;

    /**
     * @var CommandLogger
     */
    private $commandLogger;

    /**
     * @var string
     */
    private $snmpCommunity;

    /**
     * @var array|null
     */
    private $config = null;

    /**
     * @var Collection|DeviceInterface[]
     */
    private $unmatchedLocalInterfaces;

    /**
     * @var bool
     */
    private $localInterfacesLoaded = false;

    /**
     * @var array
     */
    private $remoteAddressList = [];

    public function __construct(
        string $rootDir,
        EntityManager $em,
        Encryption $encryption,
        TranslatorInterface $translator,
        Ssh $ssh,
        File $file,
        Options $options,
        AddressListProvider $addressListProvider,
        CommandLogger $commandLogger
    ) {
        parent::__construct($rootDir, $em, $encryption, $translator, $ssh, $file, $options);
        $this->addressListProvider = $addressListProvider;
        $this->commandLogger = $commandLogger;
    }

    public function readGeneralInformation(): Device
    {
        $this->interfaces = new \stdClass();

        $this->readCommandsFromDevice();

        if (null !== $this->snmpCommunity) {
            $this->device->setSnmpCommunity($this->snmpCommunity);
        }

        return $this;
    }

    /**
     * @throws QoSSyncNotSupportedException
     */
    public function versionCompare(string $edgeOsVersion, string $version, string $operator): bool
    {
        $matches = Strings::match($edgeOsVersion, '/EdgeRouter.*v(\d+)\.(\d+)\.(\d+).*/');
        if (! $matches) {
            throw new QoSSyncNotSupportedException('EdgeOS', $this->device, 'network device');
        }
        array_shift($matches);

        return version_compare(implode('.', $matches), $version, $operator);
    }

    /**
     * @throws QoSSyncNotSupportedException
     */
    public function syncQos()
    {
        if (! $this->device instanceof NetworkDevice) {
            throw new QoSSyncNotSupportedException('EdgeOS', $this->device, 'network device');
        }

        $config = new Config(
            $this->executeQosCommand($this->prependRunCmd('show traffic-control')) ?: ''
        );

        if ($this->device->getOsVersion()) {
            $maxQueueNodeNumber = $this->versionCompare($this->device->getOsVersion(), self::QOS_IP_COUNT_UNLIMITED_VERSION, '<')
                ? Config::MAX_QUEUE_NODE_NUMBER
                : Config::MAX_QUEUE_NODE_NUMBER_EXTENDED;
        } else {
            $maxQueueNodeNumber = Config::MAX_QUEUE_NODE_NUMBER_EXTENDED;
        }

        $commandBuilder = new CommandBuilder($config, $maxQueueNodeNumber);
        $commandBuilder->addBandwidthSyncCommands($this->device->getBandwidth() ?? Config::DEFAULT_ROOT_BANDWIDTH);

        $qosCustom = $this->options->get(Option::QOS_DESTINATION) === Option::QOS_DESTINATION_CUSTOM
            && $this->device->getQosEnabled() === BaseDevice::QOS_THIS;
        $qosGateway = $this->options->get(Option::QOS_DESTINATION) === Option::QOS_DESTINATION_GATEWAY
            && $this->device->isGateway();

        if ($this->options->get(Option::QOS_ENABLED) && ($qosCustom || $qosGateway)) {
            $addressList = $this->addressListProvider->getList($this->device);
            foreach ($addressList as $addressItem) {
                $ipRange = [];
                if (null !== $addressItem['netmask']) {
                    $ipRange[] = sprintf('%s/%d', long2ip($addressItem['ip_address']), $addressItem['netmask']);
                } else {
                    for ($ip = $addressItem['first_ip_address']; $ip <= $addressItem['last_ip_address']; ++$ip) {
                        $ipRange[] = sprintf('%s/%d', long2ip($ip), 32);
                    }
                }

                foreach ($ipRange as $ip) {
                    $commandBuilder->addAddressSyncCommands(
                        $ip,
                        $addressItem['service_id'],
                        $addressItem['tariff_id'],
                        $addressItem['download_speed'],
                        $addressItem['upload_speed']
                    );
                }
            }
        }

        $openCommands = [
            $this->prependRunCmd('begin'),
        ];
        $closeCommands = [
            $this->prependRunCmd('commit'),
            $this->prependRunCmd('save', true),
            $this->prependRunCmd('end'),
        ];
        $openCloseCommandsLength = Strings::length(implode(PHP_EOL, $openCommands))
            + Strings::length(implode(PHP_EOL, $closeCommands));

        $currentCommandsBatchLength = 0;
        $currentCommandsBatch = [];

        foreach ($commandBuilder->getCommands() as $commandsGroup) {
            $commandsGroup = implode(PHP_EOL, array_map([$this, 'prependRunCmd'], $commandsGroup));
            $commandsGroupLength = Strings::length($commandsGroup);

            // Check if a current commands batch length plus current iteration commands length exceeds
            // the maximum ssh packet length. If so, send the current batch.
            $totalLength = $currentCommandsBatchLength
                + $commandsGroupLength
                + $openCloseCommandsLength
                + (count($currentCommandsBatch) + 1) * Strings::length(PHP_EOL);

            if ($totalLength > Ssh::MAX_PACKET_LENGTH) {
                $this->executeQosCommand(
                    implode(PHP_EOL, array_merge($openCommands, $currentCommandsBatch, $closeCommands))
                );

                $currentCommandsBatchLength = 0;
                $currentCommandsBatch = [];
            }

            $currentCommandsBatchLength += $commandsGroupLength;
            $currentCommandsBatch[] = $commandsGroup;
        }

        if ($currentCommandsBatchLength > 0) {
            $this->executeQosCommand(
                implode(PHP_EOL, array_merge($openCommands, $currentCommandsBatch, $closeCommands))
            );
        }
    }

    /**
     * @return bool|string
     */
    private function executeQosCommand(string $command)
    {
        $info = [
            'ip' => $this->ip,
            'os' => $this->device->getOsVersion() ?? Vendor::TYPES[Vendor::EDGE_OS],
            'device' => sprintf('Device %d', $this->device->getId()),
        ];

        $this->commandLogger->logCommand($command, $info);
        $output = $this->ssh->execute($command);
        $this->commandLogger->logOutput($output, $info);

        return $output;
    }

    public function saveConfiguration(): Device
    {
        if ($this->device->getLastBackupTimestamp() >= (new \DateTime('-1 day')) && $this->connect()) {
            return $this;
        }

        $cmd = sprintf('tar -zcf %s /config', self::BACKUP_FILEPATH);
        $cmdDelete = sprintf('rm -rf %s', self::BACKUP_FILEPATH);
        $this->ssh->execute($cmd);
        try {
            $data = $this->downloadFileSsh(self::BACKUP_FILEPATH);
        } catch (\Exception $exception) {
            $this->log($exception->getMessage(), DeviceLog::STATUS_ERROR);

            return $this;
        }
        $this->ssh->execute($cmdDelete);

        $backupDirectory = $this->file->getDeviceBackupDirectory($this->device);
        $filename = sprintf('%d.tar.gz', time());
        $this->file->save($backupDirectory, $filename, $data);
        $this->device->setLastBackupTimestamp(new \DateTime());

        $this->deleteOldBackups($backupDirectory);

        return $this;
    }

    public function searchForInterfaces(): Device
    {
        foreach ($this->interfaces as $interfaceName => $interface) {
            $interface->internalId = $this->getInternalId($interfaceName);
            $interface->internalType = $this->getInternalInterfaceType($interfaceName);
            $interface->internalName = $interfaceName;

            $deviceInterface = $this->matchInterface($interface);

            if (null === $deviceInterface) {
                $deviceInterface = new DeviceInterface();
                $deviceInterface->setName($interfaceName)
                    ->setType(DeviceInterface::TYPE_UNKNOWN)
                    ->setDevice($this->device);

                $this->logAddedNewInterface($interfaceName);
                $this->em->persist($deviceInterface);
            }
            $this->interfaces->{$interfaceName}->matchedDeviceInterface = $deviceInterface;

            $guessedType = $this->getInterfaceType($interfaceName);
            if ($guessedType != DeviceInterface::TYPE_UNKNOWN) {
                $deviceInterface->setType($guessedType);
            }

            $this->updateEntityAttribute(
                $deviceInterface,
                $interface,
                'internalType'
            );

            $this->updateEntityAttribute(
                $deviceInterface,
                $interface,
                'internalId'
            );

            $this->updateEntityAttribute(
                $deviceInterface,
                $interface,
                'internalName'
            );

            $this->updateEntityAttribute(
                $deviceInterface,
                $interface,
                'macAddress'
            );
        }

        if ($this->unmatchedLocalInterfaces) {
            foreach ($this->unmatchedLocalInterfaces as $localInterface) {
                $localInterface
                    ->setInternalId(null)
                    ->setInternalName(null)
                    ->setInternalType(null);
            }
        }

        $this->em->flush();
        $this->em->refresh($this->device);

        return $this;
    }

    /**
     * @return DeviceInterface|null
     */
    private function matchInterface(NetworkInterface $remoteInterface)
    {
        if (! $this->localInterfacesLoaded) {
            $this->unmatchedLocalInterfaces = $this->device->getNotDeletedInterfaces();
            $this->localInterfacesLoaded = true;
        }

        $deviceInterface = $this->matchInterfaceByField('internalName', $remoteInterface->internalName);

        if (null === $deviceInterface) {
            $deviceInterface = $this->matchInterfaceByField('internalId', $remoteInterface->internalId);
        }

        $isVLAN = Strings::contains($remoteInterface->internalType, '.');
        if (null === $deviceInterface && $remoteInterface->macAddress && ! $isVLAN) {
            $deviceInterface = $this->matchInterfaceByMacAddress(Mac::formatView($remoteInterface->macAddress));
        }

        if (null === $deviceInterface) {
            $deviceInterface = $this->matchInterfaceByAddressList($remoteInterface->addresses);
        }

        if ($deviceInterface) {
            $this->unmatchedLocalInterfaces->removeElement($deviceInterface);
        }

        return $deviceInterface;
    }

    /**
     * @param string|int $value
     *
     * @return DeviceInterface|null
     */
    protected function matchInterfaceByField(string $field, $value)
    {
        $pa = PropertyAccess::createPropertyAccessor();

        foreach ($this->unmatchedLocalInterfaces as $unmatchedInterface) {
            if ($pa->getValue($unmatchedInterface, $field) === $value) {
                return $unmatchedInterface;
            }
        }

        return null;
    }

    /**
     * @return DeviceInterface|null
     */
    protected function matchInterfaceByMacAddress(string $macAddress)
    {
        foreach ($this->unmatchedLocalInterfaces as $unmatchedInterface) {
            if (in_array($unmatchedInterface->getType(), [DeviceInterface::BRIDGE, DeviceInterface::VLAN], true)) {
                continue;
            }

            if ($macAddress === $unmatchedInterface->getMacAddress()) {
                return $unmatchedInterface;
            }
        }

        return null;
    }

    /**
     * @param array|IpAddress[] $needleAddressList
     *
     * @return DeviceInterface|null
     */
    private function matchInterfaceByAddressList(array $needleAddressList)
    {
        if (empty($needleAddressList)) {
            return null;
        }

        if (empty($this->remoteAddressList)) {
            foreach ($this->interfaces as $interfaceName => $interface) {
                foreach ($interface->addresses as $address) {
                    $this->remoteAddressList[$interfaceName][$address->ipInt] = $address;
                }

                if (! empty($this->remoteAddressList[$interfaceName])) {
                    ksort($this->remoteAddressList[$interfaceName]);
                }
            }
        }

        ksort($needleAddressList);
        foreach ($this->unmatchedLocalInterfaces as $interface) {
            if (0 === $interface->getInterfaceIps()->count()) {
                continue;
            }

            $interfaceAddressList = [];
            foreach ($interface->getInterfaceIps() as $ip) {
                $ipAddress = new IpAddress();
                $ipAddress->ipInt = $ip->getIpRange()->getIpAddress();
                $ipAddress->ip = long2ip($ipAddress->ipInt);
                $ipAddress->netmask = $ip->getIpRange()->getNetmask() ?? 32;

                $interfaceAddressList[$ipAddress->ipInt] = $ipAddress;
            }
            ksort($interfaceAddressList);

            if (0 === count(array_diff($interfaceAddressList, $needleAddressList))) {
                return $interface;
            }
        }

        return null;
    }

    public function searchForInterfaceIpsChangedInterface(): Device
    {
        foreach ($this->interfaces as $interfaceName => $interface) {
            if (empty($interface->addresses)) {
                continue;
            }

            if ($interfaceName !== 'eth4') {
                continue;
            }

            /** @var IpAddress $address */
            foreach ($interface->addresses as $address) {
                $this->changeIpOnInterface(
                    $address->ipInt,
                    $address->netmask,
                    $address->internalId,
                    $interfaceName,
                    $interface->matchedDeviceInterface ?? null
                );
            }
        }

        return $this;
    }

    public function searchForInterfaceIps(): Device
    {
        $activeIpInternalId = [];
        $ipsForRemoval = [];

        foreach ($this->interfaces as $interfaceName => $interface) {
            if (empty($interface->addresses)) {
                continue;
            }

            $deviceInterface = $interface->matchedDeviceInterface
                ?? $this->matchInterfaceByField(self::INTERNAL_NAME, $interfaceName);
            if (! $deviceInterface) {
                continue;
            }

            $existingIps = $deviceInterface->getInterfaceIps();
            $remoteIps = new ArrayCollection();

            /** @var IpAddress $address */
            foreach ($interface->addresses as $address) {
                if (! is_int($address->ipInt)) {
                    continue;
                }
                $activeIpInternalId[] = $address->internalId;

                $deviceInterfaceIp = $this->deviceInterfaceIpRepository->findOneBy(
                    [
                        'ipRange.ipAddress' => $address->ipInt,
                        'interface' => $deviceInterface,
                    ]
                );

                if (null === $deviceInterfaceIp) {
                    $deviceInterfaceIp = new DeviceInterfaceIp();
                    $deviceInterfaceIp->setInterface($deviceInterface);
                    $deviceInterfaceIp->setInternalId($address->internalId);
                    $deviceInterfaceIp->setNatPublicIp(null);
                    $deviceInterfaceIp->getIpRange()->setCidr($address->ipInt, $address->netmask);

                    $this->setInterfaceIpAccessible($deviceInterfaceIp);

                    if ($address->ip === $this->ip) {
                        $deviceInterfaceIp->setWasLastConnectionSuccessful(true);
                    }

                    $this->logAddedNewIp($deviceInterface, $deviceInterfaceIp);

                    $this->em->persist($deviceInterfaceIp);
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

    protected function getFirewallAddressList(string $name): array
    {
        return $this->getDeviceConfigWithPrefix(sprintf('set firewall group address-group %s address', $name));
    }

    /**
     * @throws Exceptions\LoginException
     * @throws EmptyResponseException
     */
    protected function updateFirewallAddressList(string $name, array $toAdd, array $toRemove)
    {
        $commands = [$this->prependRunCmd('begin')];

        foreach ($toAdd as $addIp) {
            $commands[] = $this->prependRunCmd(
                sprintf('set firewall group address-group %s address %s', $name, $addIp)
            );
        }

        foreach ($toRemove as $removeIp) {
            $commands[] = $this->prependRunCmd(
                sprintf('delete firewall group address-group %s address %s', $name, $removeIp)
            );
        }

        $commands[] = $this->prependRunCmd('commit');
        $commands[] = sprintf('%s 2>&1', $this->prependRunCmd('save', true));
        $commands[] = $this->prependRunCmd('end');

        $res = $this->ssh->execute(implode(PHP_EOL, $commands));

        if ($res === false) {
            throw new EmptyResponseException();
        }
    }

    private function readCommandsFromDevice()
    {
        $commands = [
            'version' => [
                'command' => 'cat /etc/version',
            ],
            'ifconfig' => [
                'command' => 'sudo ifconfig -a',
                'message' => 'Invalid ifconfig response',
            ],
            'interfaces' => [
                'command' => $this->prependRunCmd('show interfaces'),
            ],
            'snmp' => [
                'command' => $this->prependRunCmd('show service snmp'),
            ],
        ];

        $responseRaw = $this->callCommands($commands);

        foreach ($commands as $property => $command) {
            $start = strpos($responseRaw, $command['startDelimiter']) + Strings::length($command['startDelimiter']);
            $end = strpos($responseRaw, $command['endDelimiter']);

            $response = trim(Strings::substring($responseRaw, $start, $end - $start));

            switch (true) {
                case $property === 'version':
                    $this->processVersionResponse($response);
                    break;
                case $property === 'ifconfig':
                    $this->processIfconfigResponse($response);
                    break;
                case $property === 'interfaces':
                    $this->processInterfacesResponse($response);
                    break;
                case $property === 'snmp':
                    $this->processServicesResponse($response);
                    break;
            }
        }
    }

    private function processIfconfigResponse(string $response)
    {
        $this->interfaces = new \stdClass();

        foreach (preg_split('~((\r?\n)|(\r\n?))~', $response) as $line) {
            $line = trim($line);

            $type = Strings::startsWith($line, 'eth')
                ? 'eth'
                : (
                    Strings::startsWith($line, 'switch')
                    ? 'switch'
                    : null
                );

            if (
                $type
                && ($parsedName = Strings::match($line, sprintf('~(%s[\d.]+)~', $type)))
            ) {
                $name = current($parsedName);
                [, $mac] = array_pad(explode('HWaddr ', $line, 2), 2, null);

                $this->interfaces->{$name} = new NetworkInterface();
                $this->interfaces->{$name}->macAddress = Mac::format($mac);
                $this->interfaces->{$name}->internalId = $this->getInternalId($name);
            }
        }
    }

    private function processInterfacesResponse(string $response)
    {
        $data = VyattaParser::parse($response);

        // Some versions of EdgeOs return more things than just interfaces.
        $data = $data['interfaces'] ?? $data;

        foreach ($data as $interfaceKey => $interfaceData) {
            list(, $interfaceName) = explode('_', $interfaceKey, 2);

            if (in_array($interfaceName, self::UNMANAGED_INTERFACES, true)) {
                continue;
            }

            $interfaceId = $this->getInternalId($interfaceName);

            if (! property_exists($this->interfaces, $interfaceName)) {
                $interface = new NetworkInterface();
                $interface->internalId = $interfaceId;
                $interface->internalType = $this->getInterfaceType($interfaceName);
                $interface->addresses = [];
                $interface->macAddress = null;
                $this->interfaces->{$interfaceName} = $interface;
            }

            $ips = [];

            if (isset($interfaceData['address'])) {
                $ips[] = $interfaceData['address'];
                for ($i = 0; $address = $interfaceData['address_' . $i] ?? false; ++$i) {
                    $ips[] = $address;
                }
            } else {
                foreach ($interfaceData as $key => $value) {
                    if (Strings::match($key, '~^vif_([a-z0-9]++)$~') && isset($value['address'])) {
                        $ips[] = $value['address'];
                        for ($i = 0; $address = $interfaceData['address_' . $i] ?? false; ++$i) {
                            $ips[] = $address;
                        }
                    }
                }
            }

            foreach ($ips as $id => $cidr) {
                $exploded = explode('/', $cidr, 2);
                $ip = $exploded[0];
                $netmask = $exploded[1] ?? 32;

                $address = new IpAddress();
                $address->ip = $ip;
                $address->netmask = (int) $netmask;
                $address->ipInt = ip2long($ip);
                $address->internalId = sprintf('%d-%d', $interfaceId, $id);

                $this->interfaces->{$interfaceName}->addresses[$address->ipInt] = $address;
            }
        }
    }

    private function processServicesResponse(string $response)
    {
        $match = Strings::match($response, '~community\ (.*)\ \{~imus');

        if (isset($match[1])) {
            // @todo
            // This should be 32 characters tops.
            // The parsing above is broken and sometimes can return a completely irrelevant long result.
            $this->snmpCommunity = Strings::substring($match[1], 0, 32);
        }
    }

    private function prependRunCmd(string $cmd, bool $runSudo = false): string
    {
        return trim(
            sprintf(
                '%s %s %s',
                $runSudo ? 'sudo' : '',
                self::CMD_RUN,
                $cmd
            )
        );
    }

    /**
     * @return array|string|null
     *
     * @throws Exceptions\LoginException
     * @throws EmptyResponseException
     */
    private function getDeviceConfigAll()
    {
        if (! $this->config) {
            $configRaw = $this->ssh->execute('vbash -c -i "show configuration commands"');
            if (! $configRaw) {
                throw new EmptyResponseException();
            }

            $this->config = explode(PHP_EOL, $configRaw);
        }

        return $this->config;
    }

    /**
     * @param $prefix
     *
     * @return array
     *
     * @throws RemoteCommandException
     */
    private function getDeviceConfigWithPrefix($prefix)
    {
        $result = [];
        $prefixLen = Strings::length($prefix);
        foreach ($this->getDeviceConfigAll() as $row) {
            if (Strings::substring($row, 0, $prefixLen) == $prefix) {
                $result[] = trim(Strings::substring($row, $prefixLen));
            }
        }

        return $result;
    }
}
