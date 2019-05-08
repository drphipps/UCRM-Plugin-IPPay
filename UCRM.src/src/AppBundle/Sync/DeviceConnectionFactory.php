<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Sync;

use AppBundle\Component\QoS\AddressListProvider;
use AppBundle\Component\QoS\CommandLogger;
use AppBundle\Entity\BaseDevice;
use AppBundle\Entity\Device as DeviceEntity;
use AppBundle\Entity\DeviceLog;
use AppBundle\Entity\NetworkDeviceIpInterface;
use AppBundle\Entity\ServiceDevice;
use AppBundle\Entity\ServiceDeviceLog;
use AppBundle\Service\Encryption;
use AppBundle\Service\Options;
use AppBundle\Sync\Exceptions\ConnectionFailed;
use AppBundle\Sync\Exceptions\LoginException;
use AppBundle\Util\File;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Doctrine\ORM\EntityManager;
use Nette\Utils\Strings;
use Symfony\Component\Translation\TranslatorInterface;

class DeviceConnectionFactory
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Encryption
     */
    private $encryption;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Ssh
     */
    private $ssh;

    /**
     * @var File
     */
    private $file;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var AddressListProvider
     */
    private $addressListProvider;

    /**
     * @var CommandLogger
     */
    private $commandLogger;

    /**
     * @param string $rootDir
     */
    public function __construct(
        $rootDir,
        EntityManager $em,
        Encryption $encryption,
        TranslatorInterface $translator,
        Ssh $ssh,
        File $file,
        Options $options,
        AddressListProvider $addressListProvider,
        CommandLogger $commandLogger
    ) {
        $this->rootDir = $rootDir;
        $this->em = $em;
        $this->encryption = $encryption;
        $this->translator = $translator;
        $this->ssh = $ssh;
        $this->file = $file;
        $this->options = $options;
        $this->addressListProvider = $addressListProvider;
        $this->commandLogger = $commandLogger;
    }

    /**
     * @return \AppBundle\Sync\Device
     *
     * @throws \Exception
     */
    public function create(BaseDevice $device)
    {
        if (! $device->getLoginUsername()) {
            throw new ConnectionFailed('Missing username for this device.');
        }

        if ($password = $device->getLoginPassword()) {
            try {
                $this->encryption->decrypt($password);
            } catch (WrongKeyOrModifiedCiphertextException $e) {
                throw new ConnectionFailed('Decryption of password failed. Wrong key or modified cipher text.');
            }
        }

        $driverClassName = $device->getDriverClassName();

        $arguments = [
            $this->rootDir,
            $this->em,
            $this->encryption,
            $this->translator,
            $this->ssh,
            $this->file,
            $this->options,
        ];

        switch ($driverClassName) {
            case AirOs::class:
            case AirOsServiceDevice::class:
                $arguments[] = $this->commandLogger;
                break;
            case EdgeOs::class:
                $arguments[] = $this->addressListProvider;
                $arguments[] = $this->commandLogger;
                break;
        }

        /** @var \AppBundle\Sync\Device $deviceDriver */
        $deviceDriver = new $driverClassName(...$arguments);

        $ips = $device->getDeviceIps();
        if (null !== $device->getManagementIpAddress()) {
            array_unshift($ips, long2ip($device->getManagementIpAddress()));
        }
        $connected = false;

        foreach ($ips as $ip) {
            $ipString = is_string($ip) ? $ip : long2ip($ip->getIpRange()->getIpAddress());
            $message = $this->translator->trans('Connecting to %deviceIp%.', ['%deviceIp%' => $ipString]);
            $this->log($device, $message, DeviceLog::STATUS_OK);

            try {
                $deviceDriver
                    ->setIp($ipString)
                    ->setDevice($device)
                    ->connect()
                    ->init();

                if ($ip instanceof NetworkDeviceIpInterface) {
                    $ip->setWasLastConnectionSuccessful(true);
                }

                $connected = true;

                break;
            } catch (\ErrorException $e) {
                if ($ip instanceof NetworkDeviceIpInterface) {
                    $ip->setWasLastConnectionSuccessful(false);
                }

                $this->log($device, $e->getMessage(), DeviceLog::STATUS_ERROR);
            } catch (LoginException $e) {
                if ($ip instanceof NetworkDeviceIpInterface) {
                    $ip->setWasLastConnectionSuccessful(false);
                }

                $message = $this->translator->trans(
                    'Connection to %deviceIp% failed. Make sure the interfaces are configured correctly.',
                    [
                        '%deviceIp%' => $ipString,
                    ]
                );
                $this->log($device, $message, DeviceLog::STATUS_ERROR);
            } catch (\Exception $e) {
                if ($ip instanceof NetworkDeviceIpInterface) {
                    $ip->setWasLastConnectionSuccessful(false);
                }

                $message = $this->translator->trans(
                    'Connecting to %deviceIp% failed.',
                    [
                        '%deviceIp%' => $ipString,
                    ]
                );
                $this->log($device, $message, DeviceLog::STATUS_ERROR);

                throw $e;
            }
        }

        if (! $connected) {
            throw new ConnectionFailed('Unable to connect to the device.');
        }

        return $deviceDriver;
    }

    /**
     * This is a duplicate of NetworkDeviceCommand::log().
     *
     * @todo Refactor to a factory
     */
    private function log(BaseDevice $device, string $message, int $status)
    {
        if ($device instanceof DeviceEntity) {
            $log = new DeviceLog();
            $log->setDevice($device);
            $log->setScript('SyncDeviceCommand');
        } elseif ($device instanceof ServiceDevice) {
            $log = new ServiceDeviceLog();
            $log->setServiceDevice($device);
            $log->setScript('SyncServiceDeviceCommand');
        } else {
            return;
        }

        $log->setCreatedDate(new \DateTime());
        $log->setMessage(Strings::fixEncoding($message));
        $log->setStatus($status);

        $this->em->persist($log);
        $this->em->flush();
    }
}
