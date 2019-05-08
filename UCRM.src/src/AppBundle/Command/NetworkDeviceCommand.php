<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Command;

use AppBundle\Entity\Device;
use AppBundle\Entity\DeviceLog;
use AppBundle\Entity\NetworkDeviceIpInterface;
use AppBundle\Entity\ServiceDevice;
use AppBundle\Entity\ServiceDeviceLog;
use AppBundle\Service\EntityManagerRecreator;
use AppBundle\Service\ExceptionTracker;
use AppBundle\Sync\Exceptions\ConnectTimeoutException;
use AppBundle\Sync\Exceptions\InvalidServerIpException;
use AppBundle\Sync\Exceptions\LoginException;
use AppBundle\Sync\Exceptions\RemoteCommandException;
use AppBundle\Sync\Exceptions\SyncException;
use Nette\Utils\Strings;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

abstract class NetworkDeviceCommand extends ContainerAwareCommand
{
    use BaseCommandTrait;

    /**
     * @var Device|ServiceDevice
     */
    protected $device;

    /**
     * @var int
     */
    protected $deviceId;

    protected function findServiceDevice()
    {
        $this->device = $this->em->find(ServiceDevice::class, $this->deviceId);
    }

    protected function findDevice()
    {
        $this->device = $this->em->find(Device::class, $this->deviceId);
    }

    protected function setLastConnectionSuccessfulOnIp(NetworkDeviceIpInterface $interfaceIp, bool $state)
    {
        $interfaceIp->setWasLastConnectionSuccessful($state);
        $this->em->persist($interfaceIp);
    }

    protected function logException(\Exception $e, string $ip = null)
    {
        if (! $e instanceof SyncException) {
            $this->getContainer()->get(ExceptionTracker::class)->captureException($e);
        }

        if ($e instanceof ConnectTimeoutException) {
            $message = $this->translator->trans('Connection timeout %ip%', ['%ip%' => $ip]);
        } elseif ($e instanceof LoginException) {
            $message = $this->translator->trans('Login failed');
        } elseif ($e instanceof RemoteCommandException) {
            $message = $this->translator->trans('Remote command failed');

            if (is_string($e->getMessage())) {
                $message = sprintf(
                    '%s. %s',
                    $message,
                    $e->getMessage()
                );
            }
        } elseif ($e instanceof InvalidServerIpException) {
            $message = $this->translator->trans('Invalid server ip in system settings');
        } else {
            $message = $e->getMessage();

            $this->logger->error(get_class($e));
            $this->logger->error(sprintf('%s (code %s)', $e->getMessage(), $e->getCode()));
            $this->logger->error(sprintf('%s:%s', $e->getFile(), $e->getLine()));
        }

        $this->log($message, DeviceLog::STATUS_ERROR);
    }

    protected function log(string $message, int $status)
    {
        $em = $this->em;
        $device = $this->device;

        if (! $em->isOpen()) {
            $em = $this->getContainer()->get(EntityManagerRecreator::class)->create($em);
            $device = $em->merge($device);
        }

        if ($this->device instanceof Device) {
            $log = new DeviceLog();
            $log->setDevice($device);
            $log->setScript(get_class($this));
        } elseif ($this->device instanceof ServiceDevice) {
            $log = new ServiceDeviceLog();
            $log->setServiceDevice($device);
            $log->setScript(get_class($this));
        } else {
            return;
        }

        $log->setCreatedDate(new \DateTime());
        $log->setMessage(Strings::fixEncoding($message));
        $log->setStatus($status);

        $em->persist($log);
        $em->flush();
    }
}
