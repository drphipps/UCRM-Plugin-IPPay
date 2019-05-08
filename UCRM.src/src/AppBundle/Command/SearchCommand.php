<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Command;

use AppBundle\Entity\Device;
use AppBundle\Entity\Option;
use AppBundle\Entity\SearchDeviceQueue;
use AppBundle\Entity\SearchServiceDeviceQueue;
use AppBundle\Entity\ServiceDevice;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class SearchCommand extends ContainerAwareCommand
{
    use BaseCommandTrait;

    const COMMAND_DEVICE = 'crm:search:device';
    const COMMAND_SERVICE_DEVICE = 'crm:search:serviceDevice';

    const DEVICE = 'device';
    const SERVICE_DEVICE = 'serviceDevice';

    const DEVICE_ID = 'deviceId';

    /**
     * @var int
     */
    private $concurrency;

    /**
     * @var int
     */
    private $wait;

    /**
     * @var int
     */
    private $pause;

    /**
     * @var int
     */
    private $timeout;

    /**
     * @var int
     */
    private $lastRegularCheckDevice;

    /**
     * @var int
     */
    private $lastRegularCheckServiceDevice;

    /**
     * @var \SplQueue
     */
    private $regularDeviceProcesses;

    /**
     * @var \SplQueue
     */
    private $regularServiceDeviceProcesses;

    /**
     * @var \SplQueue
     */
    private $regularDeviceQueue;

    /**
     * @var \SplQueue
     */
    private $regularServiceDeviceQueue;

    protected function configure()
    {
        $this->setName('crm:search')
            ->setDescription('Search devices.')
            ->addOption(
                'concurrency',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Sets limit of how many devices can be searched simultaneously.',
                '5'
            )
            ->addOption(
                'pause',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Pause between processing the search queue in seconds',
                '10'
            )
            ->addOption(
                'timeout',
                't',
                InputOption::VALUE_OPTIONAL,
                'Sets timeout for one device search command in seconds.',
                '300'
            );
    }

    /**
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->init();

        $this->concurrency = (int) $input->getOption('concurrency');
        $this->pause = (int) $input->getOption('pause');
        $this->timeout = (int) $input->getOption('timeout');

        $this->regularDeviceProcesses = new \SplQueue();
        $this->regularServiceDeviceProcesses = new \SplQueue();
        $this->regularDeviceQueue = new \SplQueue();
        $this->regularServiceDeviceQueue = new \SplQueue();

        while (true) {
            $now = time();
            $this->options->refresh();
            // wait time between search attempts (in seconds)
            $this->wait = ((int) $this->options->get(Option::SYNC_FREQUENCY, Option::SYNC_FREQUENCY_12)) * 3600;

            // devices
            $this->checkRegularNetworkDeviceProcesses();

            if ($this->lastRegularCheckDevice <= ($now - $this->wait)) {
                $this->fillRegularQueueDevice();
                $this->lastRegularCheckDevice = $now;
            }

            $this->searchDeviceQueue();

            $this->searchRegular(
                $this->regularDeviceQueue,
                $this->regularDeviceProcesses,
                self::DEVICE
            );

            // service devices
            $this->checkRegularServiceDeviceProcesses();

            if ($this->lastRegularCheckServiceDevice <= ($now - $this->wait)) {
                $this->fillRegularQueueServiceDevice();
                $this->lastRegularCheckServiceDevice = $now;
            }

            $this->searchServiceDeviceQueue();

            $this->searchRegular(
                $this->regularServiceDeviceQueue,
                $this->regularServiceDeviceProcesses,
                self::SERVICE_DEVICE
            );

            $this->em->flush();
            $this->em->clear();

            sleep($this->pause);
        }

        return 0;
    }

    private function searchDeviceQueue()
    {
        $splQueueIds = [];
        $databaseQueueIds = [];

        $this->regularDeviceQueue->rewind();

        foreach ($this->regularDeviceQueue as $device) {
            $splQueueIds[] = $device->getId();
        }

        $queue = $this->em->getRepository(SearchDeviceQueue::class)->findAll();

        foreach ($queue as $searchDeviceQueue) {
            $databaseQueueIds[] = $searchDeviceQueue->getDevice()->getId();
            $this->em->remove($searchDeviceQueue);
        }

        foreach (array_diff($databaseQueueIds, $splQueueIds) as $deviceId) {
            $this->logger->info(sprintf('Searching on device %d started.', $deviceId));
            $process = $this->startSearchingProcess($deviceId, self::COMMAND_DEVICE, self::DEVICE);
            $this->regularDeviceProcesses->enqueue($process);
        }
    }

    private function searchServiceDeviceQueue()
    {
        $splQueueIds = [];
        $databaseQueueIds = [];

        $this->regularServiceDeviceQueue->rewind();

        foreach ($this->regularServiceDeviceQueue as $device) {
            $splQueueIds[] = $device->getId();
        }

        $queue = $this->em->getRepository(SearchServiceDeviceQueue::class)->findAll();

        foreach ($queue as $searchServiceDeviceQueue) {
            $databaseQueueIds[] = $searchServiceDeviceQueue->getServiceDevice()->getId();
            $this->em->remove($searchServiceDeviceQueue);
        }

        foreach (array_diff($databaseQueueIds, $splQueueIds) as $deviceId) {
            $this->logger->info(sprintf('Searching on service device %d started.', $deviceId));
            $process = $this->startSearchingProcess($deviceId, self::COMMAND_SERVICE_DEVICE, self::SERVICE_DEVICE);
            $this->regularServiceDeviceProcesses->enqueue($process);
        }
    }

    private function fillRegularQueueDevice()
    {
        $repository = $this->em->getRepository(Device::class);
        $toBeSearched = [];
        if ($this->options->get(Option::SYNC_ENABLED)) {
            $toBeSearched = $repository->getAccessibleDevices();
        }
        $this->logger->info(sprintf('%d devices accessible.', count($toBeSearched)));

        foreach ($toBeSearched as $device) {
            $this->regularDeviceQueue->enqueue($device);
        }
    }

    private function fillRegularQueueServiceDevice()
    {
        $serviceDevices = [];
        if ($this->options->get(Option::SYNC_ENABLED)) {
            $serviceDevices = $this->em->getRepository(ServiceDevice::class)
                ->createQueryBuilder('sd')
                ->where('sd.service IS NOT NULL')
                ->getQuery()->getResult();
        }
        $this->logger->info(sprintf('%d service devices accessible.', count($serviceDevices)));

        foreach ($serviceDevices as $serviceDevice) {
            $this->regularServiceDeviceQueue->enqueue($serviceDevice);
        }
    }

    /**
     * @param \SplQueue &$queue
     */
    private function searchRegular(\SplQueue $queue, \SplQueue $processes, string $deviceType)
    {
        $commandList = sprintf(
            'crm:search:%s',
            $deviceType === self::DEVICE ? 'device' : 'serviceDevice'
        );

        $runningSearchList = $this->getRunningCommandList($commandList);
        $runningSearchListCount = count($runningSearchList);

        if ($runningSearchListCount >= $this->concurrency) {
            $this->logger->info(sprintf('Concurrency limit %d exceeded.', $this->concurrency));

            return;
        }

        $enqueuedIds = [];
        $processes->rewind();

        foreach ($processes as $process) {
            $enqueuedIds[] = $process->getOptions()[self::DEVICE_ID];
        }

        $command = $deviceType === self::DEVICE ? self::COMMAND_DEVICE : self::COMMAND_SERVICE_DEVICE;

        $queue->rewind();
        while (! $queue->isEmpty() && $runningSearchListCount < $this->concurrency) {
            $device = $queue->dequeue();
            $deviceId = $device->getId();

            if (
                array_key_exists($deviceId, $enqueuedIds) ||
                $this->isCommandRunning($runningSearchList, $deviceId, $command)
            ) {
                continue;
            }

            $process = $this->startSearchingProcess($deviceId, $command, $deviceType);
            $processes->enqueue($process);

            ++$runningSearchListCount;

            $this->logger->info(
                sprintf(
                    'Searching %s %d.',
                    $this->translateDeviceType($deviceType),
                    $deviceId
                )
            );
        }
    }

    private function startSearchingProcess(int $deviceId, string $command, string $deviceType): Process
    {
        $arguments = sprintf(
            '--env=%s',
            $this->container->get('kernel')->getEnvironment()
        );

        $cmd = sprintf(
            'php %s/console %s %d %s',
            $this->rootDir,
            $command,
            $deviceId,
            $arguments
        );

        $process = new Process($cmd);
        $process->setTimeout($this->timeout);
        $process->setOptions(array_merge($process->getOptions(), [self::DEVICE_ID => $deviceId]));

        $process->start(
            function ($type, $data) use ($deviceId, $deviceType) {
                $this->logger->info(
                    sprintf(
                        'Search command for %s %d wrote the following output to STD_%s:',
                        $this->translateDeviceType($deviceType),
                        $deviceId,
                        strtoupper($type)
                    )
                );
                $this->logger->info($data);
            }
        );

        return $process;
    }

    private function checkRegularNetworkDeviceProcesses()
    {
        $newProcesses = new \SplQueue();

        $this->regularDeviceProcesses->rewind();
        while (! $this->regularDeviceProcesses->isEmpty()) {
            $process = $this->regularDeviceProcesses->dequeue();
            $this->checkRunningProcesses($newProcesses, $process, self::DEVICE);
        }

        $this->regularDeviceProcesses = $newProcesses;
    }

    private function checkRegularServiceDeviceProcesses()
    {
        $newProcesses = new \SplQueue();

        $this->regularServiceDeviceProcesses->rewind();
        while (! $this->regularServiceDeviceProcesses->isEmpty()) {
            $process = $this->regularServiceDeviceProcesses->dequeue();
            $this->checkRunningProcesses($newProcesses, $process, self::SERVICE_DEVICE);
        }

        $this->regularServiceDeviceProcesses = $newProcesses;
    }

    private function checkRunningProcesses(\SplQueue $queue, Process $process, string $deviceType)
    {
        $deviceId = $process->getOptions()[self::DEVICE_ID];

        $timeoutError = false;

        /** @var $process Process */
        if ($process->isRunning()) {
            try {
                $process->checkTimeout();
                $queue->enqueue($process);

                return;
            } catch (ProcessTimedOutException $e) {
                $timeoutError = true;
            }
        }

        if ($timeoutError) {
            $message = sprintf(
                'Search for %s %d has timed out.',
                $this->translateDeviceType($deviceType),
                $deviceId
            );
        } elseif (0 === $process->getExitCode()) {
            $message = sprintf(
                '%s %d has been searched.',
                $this->translateDeviceType($deviceType),
                $deviceId
            );
        } else {
            $message = sprintf(
                'Search for %s %d has failed with exit code %d.',
                $this->translateDeviceType($deviceType),
                $deviceId,
                $process->getExitCode()
            );
        }

        $this->logger->info(ucfirst($message));
    }

    private function translateDeviceType(string $deviceType): string
    {
        return $deviceType === self::DEVICE ? 'device' : 'service device';
    }
}
