<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Command;

use AppBundle\Entity\Device;
use AppBundle\Entity\General;
use AppBundle\Entity\ServiceDevice;
use AppBundle\Facade\OptionsFacade;
use AppBundle\Repository\DeviceRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class SyncCommand extends ContainerAwareCommand
{
    use BaseCommandTrait;

    const COMMAND_DEVICE = 'crm:sync:device';
    const COMMAND_SERVICE_DEVICE = 'crm:sync:serviceDevice';

    /**
     * @var int
     */
    private $concurrency;

    /**
     * @var int
     */
    private $sleep;

    /**
     * @var int
     */
    private $pause;

    /**
     * @var int
     */
    private $timeout;

    protected function configure()
    {
        $this->setName('crm:sync')
            ->setDescription('Synchronize devices.')
            ->addOption(
                'concurrency',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Sets limit of how many devices can be synced simultaneously.',
                '10'
            )
            ->addOption(
                'wait',
                'w',
                InputOption::VALUE_OPTIONAL,
                'Sets time to sleep in seconds between sync attempts.',
                '10'
            )
            ->addOption(
                'pause',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Pause between processing the sync queue',
                '2'
            )
            ->addOption(
                'timeout',
                't',
                InputOption::VALUE_OPTIONAL,
                'Sets timeout for one device sync command in seconds.',
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
        $this->sleep = (int) $input->getOption('wait');
        $this->pause = (int) $input->getOption('pause');
        $this->timeout = (int) $input->getOption('timeout');

        $this->sync();

        return 0;
    }

    /**
     * Gets all unsynchronized devices and runs command for sync single device.
     */
    private function sync()
    {
        $arguments = sprintf('--env=%s', $this->container->get('kernel')->getEnvironment());

        $deviceQueue = [];
        $serviceDeviceQueue = [];
        $deviceProcesses = [];
        $serviceDeviceProcesses = [];
        $deviceRepository = $this->em->getRepository(Device::class);
        $serviceDeviceRepository = $this->em->getRepository(ServiceDevice::class);

        while (true) {
            $this->em->clear();
            $this->options->refresh();

            $this->syncDevices(
                $deviceQueue,
                $deviceProcesses,
                $deviceRepository,
                $arguments
            );

            $this->syncServiceDevices(
                $serviceDeviceQueue,
                $serviceDeviceProcesses,
                $serviceDeviceRepository,
                $arguments
            );

            // If there is a device to be synchronized make a pause. Sleep longer otherwise
            $sleep = $deviceQueue || $serviceDeviceQueue ? $this->pause : $this->sleep;
            $this->logger->info(sprintf('Sleep for %d seconds.', $sleep));
            sleep($sleep);
        }
    }

    private function syncDevices(
        array &$queue,
        array &$processes,
        DeviceRepository $repository,
        string $arguments
    ) {
        foreach ($processes as $id => $process) {
            $timeoutError = false;

            /** @var $process Process */
            if ($process->isRunning()) {
                try {
                    $process->checkTimeout();
                    continue;
                } catch (ProcessTimedOutException $e) {
                    $timeoutError = true;
                }
            }

            if ($timeoutError) {
                $message = sprintf('Synchronization for device %d has timed out.', $id);
            } elseif ($process->getExitCode() === 0) {
                $message = sprintf('Device %d has been synchronized.', $id);
            } else {
                $message = sprintf(
                    'Synchronization for device %d has failed with exit code %d.',
                    $id,
                    $process->getExitCode()
                );
            }

            $device = $repository->find($id);
            if ($device) {
                $device->setSynchronized(true);
                $device->setQosSynchronized(true);
                $this->em->flush();
            }

            $this->logger->info($message);

            unset($processes[$id]);
        }

        if (! $queue && ! $processes) {
            $suspendSynchronized = $this->options->getGeneral(General::SUSPEND_SYNCHRONIZED);
            if (! $suspendSynchronized) {
                // If there are no queued devices, start a new queue with all accessible suspendable devices.
                $toBeSynchronized = $repository->getAccessibleSuspendDevices();

                $this->logger->info(sprintf('%d devices accessible.', count($toBeSynchronized)));

                $queue = array_map(
                    function (Device $device) {
                        $device->setSynchronized(false);

                        return $device->getId();
                    },
                    $toBeSynchronized
                );

                $this->getContainer()->get(OptionsFacade::class)->updateGeneral(General::SUSPEND_SYNCHRONIZED, '1');
                $this->em->flush();
            }

            $queue = array_unique(
                array_merge(
                    $queue,
                    array_map(
                        function (Device $device) {
                            return $device->getId();
                        },
                        $repository->getAccessibleUnsynchronizedNetworkDevices()
                    )
                ),
                SORT_REGULAR
            );
        }

        while ($queue) {
            $runningSyncList = $this->getRunningCommandList(self::COMMAND_DEVICE);

            // Wait if the concurrency is exceeded.
            if (count($runningSyncList) >= $this->concurrency) {
                $this->logger->info(sprintf('Concurrency limit %d exceeded.', $this->concurrency));
                break;
            }

            $id = array_shift($queue);

            // If the sync command for this device is already running, skip it.
            if (array_key_exists($id, $processes) ||
                $this->isCommandRunning($runningSyncList, $id, self::COMMAND_DEVICE)
            ) {
                continue;
            }

            // Run SyncDeviceCommand asynchronously.
            $process = new Process(
                sprintf(
                    'php %s/console %s %d %s',
                    $this->rootDir,
                    self::COMMAND_DEVICE,
                    $id,
                    $arguments
                )
            );
            $processes[$id] = $process;
            $process->setTimeout($this->timeout);
            $process->start(
                function (string $type, string $data) use ($id) {
                    $this->logger->info(
                        sprintf(
                            'Sync command for device %d wrote the following output to STD_%s:',
                            $id,
                            strtoupper($type)
                        )
                    );
                    $this->logger->info($data);
                }
            );

            $this->logger->info(sprintf('Syncing device %d.', $id));
        }
    }

    private function syncServiceDevices(
        array &$queue,
        array &$processes,
        EntityRepository $repository,
        string $arguments
    ) {
        foreach ($processes as $id => $process) {
            $timeoutError = false;

            /** @var $process Process */
            if ($process->isRunning()) {
                try {
                    $process->checkTimeout();
                    continue;
                } catch (ProcessTimedOutException $e) {
                    $timeoutError = true;
                }
            }

            if ($timeoutError) {
                $message = sprintf('Synchronization for service device %d has timed out.', $id);
            } elseif ($process->getExitCode() === 0) {
                $message = sprintf('Service device %d has been synchronized.', $id);
            } else {
                $message = sprintf(
                    'Synchronization for service device %d has failed with exit code %d.',
                    $id,
                    $process->getExitCode()
                );
            }

            $device = $repository->find($id);
            if ($device) {
                $device->setQosSynchronized(true);
                $this->em->flush();
            }

            $this->logger->info($message);

            unset($processes[$id]);
        }

        if (! $queue && ! $processes) {
            $toBeSynchronized = $repository->findBy(
                [
                    'qosSynchronized' => false,
                ]
            );

            $this->logger->info(sprintf('%d service devices out of sync.', count($toBeSynchronized)));
            $queue = array_map(
                function (ServiceDevice $device) {
                    return $device->getId();
                },
                $toBeSynchronized
            );
        }

        while ($queue) {
            $runningSyncList = $this->getRunningCommandList(self::COMMAND_SERVICE_DEVICE);

            // Wait if the concurrency is exceeded.
            if (count($runningSyncList) >= $this->concurrency) {
                $this->logger->info(sprintf('Concurrency limit %d exceeded.', $this->concurrency));
                break;
            }

            $id = array_shift($queue);

            // If the sync command for this device is already running, skip it.
            if (array_key_exists($id, $processes) ||
                $this->isCommandRunning($runningSyncList, $id, self::COMMAND_SERVICE_DEVICE)
            ) {
                continue;
            }

            // Run SyncDeviceCommand asynchronously.
            $process = new Process(
                sprintf(
                    'php %s/console %s %d %s',
                    $this->rootDir,
                    self::COMMAND_SERVICE_DEVICE,
                    $id,
                    $arguments
                )
            );
            $processes[$id] = $process;
            $process->setTimeout($this->timeout);
            $process->start(
                function (string $type, string $data) use ($id) {
                    $this->logger->info(
                        sprintf(
                            'Sync command for service device %d wrote the following output to STD_%s:',
                            $id,
                            strtoupper($type)
                        )
                    );
                    $this->logger->info($data);
                }
            );

            $this->logger->info(sprintf('Syncing service device %d.', $id));
        }
    }
}
