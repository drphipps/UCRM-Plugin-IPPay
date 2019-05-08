<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Command;

use AppBundle\Component\Ping\DeviceManager;
use AppBundle\Component\Ping\DevicePing;
use AppBundle\Component\Ping\DevicePingCollection;
use AppBundle\Component\Ping\PingDataAggregator;
use AppBundle\Entity\BaseDevice;
use AppBundle\Entity\Device;
use AppBundle\Entity\DeviceLog;
use AppBundle\Entity\Option;
use AppBundle\Entity\ServiceDevice;
use AppBundle\Entity\ServiceDeviceLog;
use AppBundle\Entity\User;
use AppBundle\Repository\DeviceRepository;
use AppBundle\Service\Email\EmailEnqueuer;
use AppBundle\Service\Options;
use AppBundle\Service\ServiceOutageUpdater;
use AppBundle\Util\Message;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Nette\Utils\Strings;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class PingDevicesCommand extends ContainerAwareCommand
{
    use BaseCommandTrait;

    const PING_CONCURRENCY = 1000; // how many IP addresses to ping at one run
    const PING_PACKET_COUNT = 21; // how many packets to send with each ping
    const PING_PROCESS_TIMEOUT = 360;
    const PING_TIME_BETWEEN_PACKETS = 2; // minimum amount of time (ms) between sending a ping packet to any target
    const SEND_NOTIFICATION_ERROR_COUNT = 3;
    const WAIT_BETWEEN_QUEUES = 60; // seconds

    const DOWN = 'down';
    const REPAIRED = 'repaired';
    const UNREACHABLE = 'unreachable';

    /**
     * @var DeviceRepository
     */
    private $deviceRepository;

    /**
     * @var EntityRepository
     */
    private $serviceDeviceRepository;

    /**
     * @var array
     */
    private $notifications = [
        self::DOWN => [],
        self::REPAIRED => [],
        self::UNREACHABLE => [],
    ];

    /**
     * @var DevicePingCollection|DevicePing[]
     */
    private $pingQueue;

    /**
     * @var DeviceManager
     */
    private $deviceManager;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var float
     */
    private $outageThreshold;

    /**
     * @var ServiceOutageUpdater
     */
    private $serviceOutageUpdater;

    /**
     * @var bool
     */
    private $needsClientsStatusUpdate = false;

    protected function configure()
    {
        $this->setName('crm:ping:devices')
            ->setDescription('Ping devices.');
    }

    /**
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->init();

        $this->pingQueue = new DevicePingCollection();
        $this->deviceRepository = $this->em->getRepository(Device::class);
        $this->serviceDeviceRepository = $this->em->getRepository(ServiceDevice::class);
        $this->deviceManager = $this->container->get(DeviceManager::class);
        $this->options = $this->container->get(Options::class);
        $this->connection = $this->getContainer()->get('doctrine.dbal.default_connection');
        $this->outageThreshold = $this->options->get(Option::PING_OUTAGE_THRESHOLD, 15) / 100;
        $this->serviceOutageUpdater = $this->container->get(ServiceOutageUpdater::class);

        $this->logger->info('ping command started');
        $this->startPingLoop();

        return 0;
    }

    private function startPingLoop()
    {
        while (true) {
            $time = time();
            $this->createPingQueue();
            /** @var DevicePingCollection $queue */
            $queue = $this->pingQueue->filter(
                function (DevicePing $device) use ($time) {
                    return $device->canBePinged($time);
                }
            );

            $queueCount = $queue->count();
            $this->logger->info(sprintf('start ping %d / %d devices', $queueCount, $this->pingQueue->count()));
            if ($queueCount > 0) {
                $pings = $this->pingDevices($queue);
                $this->processPings($queue, $pings);
            }

            $this->sendNotifications();
            $queueDuration = time() - $time;

            $this->logger->info(sprintf('done (%d devices, took %ds)', $queueCount, $queueDuration));
            $this->em->clear();
            $this->options->refresh();

            if ($this->needsClientsStatusUpdate) {
                $this->serviceOutageUpdater->update();
                $this->needsClientsStatusUpdate = false;
            }

            sleep(self::WAIT_BETWEEN_QUEUES);
        }
    }

    /**
     * Loads new devices from database and updates ping error counts in existing devices.
     */
    private function createPingQueue()
    {
        $devices = $this->deviceManager->getDeviceQueue();

        foreach ($devices as $device) {
            $queueDevice = $this->pingQueue->find($device);
            if ($queueDevice) {
                $device->setLastPing($queueDevice->getLastPing());
                $this->pingQueue->removeElement($queueDevice);
            }

            $this->pingQueue->add($device);
        }

        foreach ($this->pingQueue as $device) {
            if (null === $devices->find($device)) {
                $this->pingQueue->removeElement($device);
            }
        }
    }

    /**
     * @param DevicePingCollection|DevicePing[] $devices
     */
    private function pingDevices(DevicePingCollection $devices): string
    {
        $pings = [];
        $ipChunks = array_chunk($devices->getIpAddresses(), self::PING_CONCURRENCY);

        foreach ($ipChunks as $chunk) {
            $process = new Process(
                sprintf(
                    'fping -i %d -q -C %d %s 2>&1',
                    self::PING_TIME_BETWEEN_PACKETS,
                    self::PING_PACKET_COUNT,
                    implode(' ', $chunk)
                )
            );
            $process->setTimeout(self::PING_PROCESS_TIMEOUT);
            $exit = $process->run();
            $output = $process->getOutput();
            if (stripos($output, 'these options are too risky for mere mortals') !== false) {
                $this->logger->error('This command must be executed as ROOT.');
            } elseif ($exit > 2) {
                // we can safely ignore 0, 1 and 2 - see DIAGNOSTICS at http://fping.sourceforge.net/man/
                $this->logger->error(sprintf('fping failed: %s', $output));
            }
            $pings[] = $output;
        }

        return implode(PHP_EOL, $pings);
    }

    /**
     * @param DevicePingCollection|DevicePing[] $devices
     */
    private function processPings(DevicePingCollection $devices, string $pings)
    {
        $pings = array_filter(explode(PHP_EOL, $pings));
        $timestamp = time();
        $processed = 0;

        foreach ($pings as $ping) {
            $line = explode(' : ', $ping);
            if (count($line) !== 2) {
                continue;
            }

            $ip = trim($line[0]);
            $measurements = trim($line[1]);

            $device = $devices->findByIpAddress($ip);
            $measurements = explode(' ', $measurements);
            if (! $device || count($measurements) !== self::PING_PACKET_COUNT) {
                continue;
            }

            // first measurement thrown away for better accuracy
            $successful = 0;
            $latencySum = 0.0;
            for ($i = 1; $i < self::PING_PACKET_COUNT; ++$i) {
                if ($measurements[$i] !== '-') {
                    $latencySum += ((float) $measurements[$i]);
                    ++$successful;
                }
            }

            $pingCount = self::PING_PACKET_COUNT - 1;
            $latency = $latencySum / $pingCount;
            $packetLoss = 1 - ($successful / $pingCount);

            $device->setLastPing($timestamp);
            $device->setLatency($latency);
            $device->setPacketLoss($packetLoss);
            $device->setDown($packetLoss >= $this->outageThreshold);

            ++$processed;
        }

        if ($processed > 0) {
            $this->saveResults($devices);
        }
    }

    private function saveResults(DevicePingCollection $devices)
    {
        $networkPoints = [];
        $servicePoints = [];

        /** @var DevicePing $device */
        foreach ($devices as $device) {
            if ($device->getType() === DevicePing::TYPE_NETWORK) {
                if ($device->createStatistics()) {
                    $networkPoints[] = $this->createPoint($device);
                }

                if ($device->isDown()) {
                    $this->markDeviceDown($device, $devices, $this->deviceRepository);
                } else {
                    $this->markDeviceUp($device, $this->deviceRepository);
                }
            } else {
                if ($device->createStatistics()) {
                    $servicePoints[] = $this->createPoint($device);
                }

                if ($device->isDown()) {
                    $this->markDeviceDown($device, $devices, $this->serviceDeviceRepository);
                } else {
                    $this->markDeviceUp($device, $this->serviceDeviceRepository);
                }
            }
        }

        if ($networkPoints) {
            $this->savePoints(PingDataAggregator::TABLE_PING_RAW, $networkPoints);
        }
        if ($servicePoints) {
            $this->savePoints(PingDataAggregator::TABLE_PING_SERVICE_RAW, $servicePoints);
        }
        $this->em->flush();
    }

    private function createPoint(DevicePing $devicePing): array
    {
        $time = new \DateTimeImmutable();
        $time->setTimestamp($devicePing->getLastPing());

        return [
            'device_id' => $devicePing->getDeviceId(),
            'ping' => $devicePing->getLatency(),
            'packet_loss' => $devicePing->getPacketLoss(),
            'time' => $time->format('Y-m-d H:i:sO'), // time zone included
        ];
    }

    private function savePoints(string $table, array $points)
    {
        $query = sprintf('INSERT INTO %s (device_id, ping, packet_loss, time) VALUES ', $table);
        $values = [];
        $parameters = [];

        foreach ($points as $point) {
            $values[] = '(?, ?, ?, ?)';
            $parameters[] = $point['device_id'];
            $parameters[] = $point['ping'];
            $parameters[] = $point['packet_loss'];
            $parameters[] = $point['time'];
        }

        $query = sprintf('%s%s', $query, implode(',', $values));
        $this->connection->executeUpdate($query, $parameters);
    }

    /**
     * If Device is returned, down notification must be sent.
     */
    private function markDeviceDown(DevicePing $devicePing, DevicePingCollection $devices, EntityRepository $repository)
    {
        /** @var Device|ServiceDevice $device */
        $device = $repository->find($devicePing->getDeviceId());
        if (! $device) {
            $this->logger->info(sprintf('Device %d not found, skipping.', $devicePing->getDeviceId()));

            return;
        }
        $device->setPingErrorCount($device->getPingErrorCount() + 1);
        $status = BaseDevice::STATUS_DOWN;

        // Set status to UNREACHABLE if device has no online parent(s)
        $parents = $devicePing->getType() === DevicePing::TYPE_NETWORK
            ? $device->getParents()
            : [$device->getInterface()->getDevice()];
        foreach ($parents as $parent) {
            $status = BaseDevice::STATUS_UNREACHABLE;

            $queueParent = $devices->findByDeviceId($parent->getId(), DevicePing::TYPE_NETWORK);
            if (
                ($queueParent && ! $queueParent->isDown()) ||
                $parent->getStatus() === BaseDevice::STATUS_ONLINE
            ) {
                $status = BaseDevice::STATUS_DOWN;
                break;
            }
        }

        // set device as offline only after X errors
        if ($device->getPingErrorCount() >= self::SEND_NOTIFICATION_ERROR_COUNT) {
            // create outage if not already exists
            $outage = $this->deviceManager->findCurrentOutage($device);
            if (! $outage) {
                $outage = $this->deviceManager->createOutage($device);
                $device->addOutage($outage);

                if ($device instanceof ServiceDevice) {
                    $this->needsClientsStatusUpdate = true;
                }
            }

            // change status if different
            if ($device->getStatus() !== $status) {
                $device->setStatus($status);

                // no outage logging for UNREACHABLE devices
                if ($device->getStatus() === BaseDevice::STATUS_DOWN) {
                    $this->log($this->translator->trans('Device outage detected.'), DeviceLog::STATUS_ERROR, $device);
                }
            }

            // send DOWN notification if allowed
            if ($this->canSendNotificationForDevice($device)) {
                $this->addNotification($device);
            }
        }
    }

    private function markDeviceUp(DevicePing $devicePing, EntityRepository $repository)
    {
        /** @var BaseDevice $device */
        $device = $repository->find($devicePing->getDeviceId());
        if (! $device) {
            $this->logger->info(sprintf('Device %d not found, skipping.', $devicePing->getDeviceId()));

            return;
        }
        $sendNotification = false;

        // end outage and create log entry only if changing from offline statuses
        if (in_array($device->getStatus(), BaseDevice::OFFLINE_STATUSES, true)) {
            $this->deviceManager->endOutage($device);

            // back online log and REPAIRED notification only for DOWN status
            if ($device->getStatus() === BaseDevice::STATUS_DOWN) {
                $this->log($this->translator->trans('Device back online.'), DeviceLog::STATUS_OK, $device);
                $sendNotification = true;
            }
        }

        $device->setStatus(BaseDevice::STATUS_ONLINE);
        $device->setPingErrorCount(0);

        if ($device instanceof ServiceDevice && $device->getService()->hasOutage()) {
            $this->needsClientsStatusUpdate = true;
        }

        if ($sendNotification && $this->canSendNotificationForDevice($device)) {
            $this->addNotification($device);
        }
    }

    private function log(string $message, int $status, BaseDevice $device)
    {
        if ($device instanceof Device) {
            $log = new DeviceLog();
            $log->setDevice($device);
        } elseif ($device instanceof ServiceDevice) {
            $log = new ServiceDeviceLog();
            $log->setServiceDevice($device);
        } else {
            throw new \InvalidArgumentException('Device class not loggable.');
        }

        $log->setCreatedDate(new \DateTime());
        $log->setMessage(Strings::fixEncoding($message));
        $log->setScript('PingDevicesCommand');
        $log->setStatus($status);

        $this->em->persist($log);
    }

    private function addNotification(BaseDevice $device)
    {
        switch ($device->getStatus()) {
            case BaseDevice::STATUS_DOWN:
                $this->notifications[self::DOWN][] = $device;
                break;
            case BaseDevice::STATUS_ONLINE:
                $this->notifications[self::REPAIRED][] = $device;
                break;
            case BaseDevice::STATUS_UNREACHABLE:
                $this->notifications[self::UNREACHABLE][] = $device;
                break;
        }
    }

    private function sendNotifications()
    {
        $notifications = [];

        // filter which notification types we can send
        foreach ($this->notifications as $type => $devices) {
            if (! $this->canSendNotifications($type)) {
                $this->notifications[$type] = [];
                continue;
            }

            $notifications = array_merge($notifications, $devices);
        }

        if (count($notifications) === 0 || ! $this->options->get(Option::MAILER_SENDER_ADDRESS)) {
            return;
        }

        // sort devices by users (default user gets them all)
        $defaultUserId = $this->options->get(Option::NOTIFICATION_PING_USER);
        $defaultUser = $defaultUserId ? $this->em->getRepository(User::class)->find($defaultUserId) : null;
        if ($defaultUser && $defaultUser->isDeleted()) {
            $defaultUser = null;
        }
        $byUsers = [];
        $users = [];
        if ($defaultUser) {
            $users[$defaultUser->getId()] = $defaultUser;
        }

        /** @var BaseDevice $device */
        foreach ($notifications as $device) {
            if ($user = $device->getPingNotificationUser()) {
                $users[$user->getId()] = $user;
                $byUsers[$user->getId()][] = $device;
            }

            if ($defaultUser && $defaultUser !== $user) {
                $byUsers[$defaultUser->getId()][] = $device;
            }
        }

        // send devices by users, every user gets all the other devices as well, just in different template section
        foreach ($byUsers as $userId => $devices) {
            /** @var User $user */
            $user = $users[$userId];
            if (! $user->getEmail()) {
                $this->logger->debug(
                    sprintf('skipping notification to user ID %d, no email present', $user->getId())
                );

                continue;
            }
            $this->logger->debug(
                sprintf('sending notification to %s', $user->getEmail())
            );

            // default user has all devices already
            if ($defaultUser && $defaultUser->getId() === $userId) {
                $otherDevices = [];
            } else {
                $otherDevices = array_filter(
                    $notifications,
                    function (BaseDevice $device) use ($userId) {
                        return ! (
                            $device->getPingNotificationUser() &&
                            $device->getPingNotificationUser()->getId() === $userId
                        );
                    }
                );
            }

            $this->sendNotification($user, $devices, $otherDevices);
        }

        // This is required for proper disconnecting from a mail server in endless loop.
        $transport = $this->container->get('mailer')->getTransport();
        if ($transport->isStarted()) {
            $transport->stop();
        }

        $this->notifications = [
            self::DOWN => [],
            self::REPAIRED => [],
            self::UNREACHABLE => [],
        ];
    }

    /**
     * @param array|BaseDevice[] $devices
     * @param array|BaseDevice[] $otherDevices
     */
    private function sendNotification(User $user, array $devices, array $otherDevices)
    {
        $this->em->transactional(function () use ($user, $devices, $otherDevices) {
            $devices = $this->getDevicesByStatus($devices);
            $otherDevices = count($otherDevices) > 0 ? $this->getDevicesByStatus($otherDevices, false) : false;

            $message = new Message();
            $message->setSubject($this->translator->trans('Ping notification'));
            $message->setFrom($this->options->get(Option::MAILER_SENDER_ADDRESS));
            $message->setSender($this->options->get(Option::MAILER_SENDER_ADDRESS) ?: null);
            $message->setTo($user->getEmail());
            $message->setBody(
                $this->container->get('twig')->render(
                    'email/admin/ping.html.twig',
                    [
                        'devices' => $devices,
                        'otherDevices' => $otherDevices,
                    ]
                ),
                'text/html'
            );

            $this->container->get(EmailEnqueuer::class)->enqueue($message, EmailEnqueuer::PRIORITY_MEDIUM);
        });
    }

    /**
     * @param array|BaseDevice[] $devices
     */
    private function getDevicesByStatus(array $devices, bool $updateTimestamp = true): array
    {
        $now = new \DateTime();
        $down = $repaired = $unreachable = [];
        foreach ($devices as $device) {
            switch ($device->getStatus()) {
                case BaseDevice::STATUS_DOWN:
                    $down[] = $device;
                    break;
                case BaseDevice::STATUS_ONLINE:
                    $repaired[] = $device;
                    break;
                case BaseDevice::STATUS_UNREACHABLE:
                    $unreachable[] = $device;
                    break;
            }

            if ($updateTimestamp) {
                $device->setPingNotificationSent($now);
                $device->setPingNotificationSentStatus($device->getStatus());
            }
        }

        if ($updateTimestamp) {
            $this->em->flush();
        }

        return [
            'down' => $down,
            'repaired' => $repaired,
            'unreachable' => $unreachable,
        ];
    }

    private function canSendNotificationForDevice(BaseDevice $device): bool
    {
        return
            $device->isSendPingNotifications()
            && (
                ! $device->getPingNotificationSent()
                || $device->getPingNotificationSentStatus() !== $device->getStatus()
            );
    }

    /**
     * @return bool
     */
    private function canSendNotifications(string $type)
    {
        switch ($type) {
            case self::DOWN:
                return $this->options->get(Option::NOTIFICATION_PING_DOWN, false);
                break;
            case self::REPAIRED:
                return $this->options->get(Option::NOTIFICATION_PING_REPAIRED, false);
                break;
            case self::UNREACHABLE:
                return $this->options->get(Option::NOTIFICATION_PING_UNREACHABLE, false);
                break;
        }

        return false;
    }
}
