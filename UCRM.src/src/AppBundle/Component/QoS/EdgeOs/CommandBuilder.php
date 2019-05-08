<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\QoS\EdgeOs;

use Nette\Utils\Strings;

class CommandBuilder
{
    const GROUP_PATTERNS = [
        '/^(set traffic-control advanced-queue root queue \d+)/',
        '/^(set traffic-control advanced-queue branch queue \d+)/',
        '/^(set traffic-control advanced-queue leaf queue \d+)/',
        '/^(set traffic-control advanced-queue filters match \d+)/',
    ];

    /**
     * @var Config
     */
    private $config;

    /**
     * @var array
     */
    private $commands = [];

    /**
     * @var array|ConfigItemTariff[]
     */
    private $tariffs = [];

    /**
     * @var array|ConfigItemService[]
     */
    private $services = [];

    /**
     * @var array|ConfigItemIp[]
     */
    private $ips = [];

    /**
     * @var array|ConfigItemTariff[]
     */
    private $newTariffs = [];

    /**
     * @var array|ConfigItemService[]
     */
    private $newServices = [];

    /**
     * @var array|ConfigItemIp[]
     */
    private $newIps = [];

    /**
     * @var int
     */
    private $maxQueueNodeNumber;

    public function __construct(Config $config, int $maxQueueNodeNumber)
    {
        $this->config = $config;
        $this->maxQueueNodeNumber = $maxQueueNodeNumber;

        $this->init();
    }

    public function addBandwidthSyncCommands(float $bandwidth)
    {
        if ($this->config->isRootBandwidthChangeable() && $this->config->getRootBandwidth() !== $bandwidth) {
            $this->config->setRootBandwidth($bandwidth);
            $this->addSetCommand(
                sprintf('root queue %d bandwidth %smbit', $this->maxQueueNodeNumber, $bandwidth)
            );
        }
    }

    public function addAddressSyncCommands(
        string $ipString,
        int $serviceId,
        int $tariffId,
        float $downloadSpeed = null,
        float $uploadSpeed = null
    ) {
        // Tariff speeds are calculated later, but it can not be create without speeds, so for now lets set these.
        $tariff = $this->config->getTariff($tariffId)
            ?? $this->createTariff($tariffId, $downloadSpeed, $uploadSpeed);

        $service = $this->config->getService($serviceId);
        if ($service) {
            if ($service->tariff->id !== $tariff->id) {
                $service->tariff = $tariff;
                $this->addSetCommand(
                    sprintf('leaf queue %d parent %d', $service->uploadNodeNumber, $tariff->uploadNodeNumber)
                );
                $this->addSetCommand(
                    sprintf('leaf queue %d parent %d', $service->downloadNodeNumber, $tariff->downloadNodeNumber)
                );
            }

            if ($service->uploadSpeed !== $uploadSpeed) {
                $service->uploadSpeed = $uploadSpeed;
                $this->addSetCommand(
                    sprintf('leaf queue %d bandwidth %smbit', $service->uploadNodeNumber, $uploadSpeed)
                );
            }

            if ($service->downloadSpeed !== $downloadSpeed) {
                $service->downloadSpeed = $downloadSpeed;
                $this->addSetCommand(
                    sprintf('leaf queue %d bandwidth %smbit', $service->downloadNodeNumber, $downloadSpeed)
                );
            }
        } else {
            $service = $this->createService($serviceId, $tariff, $downloadSpeed, $uploadSpeed);
        }

        $ip = $this->config->getIp($ipString);
        if ($ip) {
            if ($ip->service->id !== $service->id) {
                $ip->service = $service;
                $this->addSetCommand(
                    sprintf('filters match %d target %d', $ip->uploadNodeNumber, $service->uploadNodeNumber)
                );
                $this->addSetCommand(
                    sprintf('filters match %d target %d', $ip->downloadNodeNumber, $service->downloadNodeNumber)
                );
            }
        } else {
            $ip = $this->createIp($ipString, $service);
        }

        $this->tariffs[$tariff->id] = $tariff;
        $this->services[$service->id] = $service;
        $this->ips[$ip->ip] = $ip;
    }

    public function getCommands(): array
    {
        $this->addTariffsSpeedSetCommands();
        $this->addIpsRemoveCommands();
        $this->addServicesRemoveCommands();
        $this->addTariffsRemoveCommands();

        return $this->commands;
    }

    private function init()
    {
        if (! $this->config->hasRoot()) {
            $this->addRootCreateCommands();
        }

        if (! $this->config->hasQueueType()) {
            $this->addQueueTypeCreateCommands();
        }
    }

    private function addRootCreateCommands()
    {
        $this->addSetCommand(sprintf('root queue %d', $this->maxQueueNodeNumber));
        $this->addSetCommand(sprintf('root queue %d attach-to global', $this->maxQueueNodeNumber));
        $this->addSetCommand(
            sprintf(
                'root queue %d bandwidth %smbit',
                $this->maxQueueNodeNumber,
                $this->config->getRootBandwidth()
            )
        );
        $this->addSetCommand(
            sprintf('root queue %d description %s', $this->maxQueueNodeNumber, Config::UCRM_IDENTIFIER)
        );
    }

    private function addQueueTypeCreateCommands()
    {
        $this->addSetCommand(sprintf('queue-type fq-codel %s_FQ_CODEL', Config::UCRM_IDENTIFIER));
    }

    private function createTariff(int $id, float $downloadSpeed = null, float $uploadSpeed = null): ConfigItemTariff
    {
        $tariff = $this->newTariffs[$id] ?? null;
        if (! $tariff) {
            $tariff = new ConfigItemTariff();
            $tariff->id = $id;

            $this->newTariffs[$id] = $tariff;
        }

        if (null === $tariff->uploadNodeNumber && null !== $uploadSpeed) {
            $tariff->uploadSpeed = $uploadSpeed;
            $tariff->uploadNodeNumber = $this->addTariffCreateCommands(
                $id,
                $uploadSpeed,
                Config::DIRECTION_UPLOAD
            );
        }

        if (null === $tariff->downloadNodeNumber && null !== $downloadSpeed) {
            $tariff->downloadSpeed = $downloadSpeed;
            $tariff->downloadNodeNumber = $this->addTariffCreateCommands(
                $id,
                $downloadSpeed,
                Config::DIRECTION_DOWNLOAD
            );
        }

        return $tariff;
    }

    private function addTariffCreateCommands(int $tariffId, float $speed, string $direction): int
    {
        $nodeNumber = $this->config->getNextQueueNodeNumber($this->maxQueueNodeNumber);

        $this->addSetCommand(sprintf('branch queue %d', $nodeNumber));
        $this->addSetCommand(sprintf('branch queue %d bandwidth %smbit', $nodeNumber, $speed));
        $this->addSetCommand(sprintf('branch queue %d parent %d', $nodeNumber, $this->maxQueueNodeNumber));
        $this->addSetCommand(
            sprintf(
                'branch queue %d description %s_tariff_%d_%s',
                $nodeNumber,
                Config::UCRM_IDENTIFIER,
                $tariffId,
                $direction
            )
        );

        return $nodeNumber;
    }

    private function createService(
        int $id,
        ConfigItemTariff $tariff,
        float $downloadSpeed = null,
        float $uploadSpeed = null
    ): ConfigItemService {
        $service = $this->newServices[$id] ?? null;
        if (! $service) {
            $service = new ConfigItemService();
            $service->id = $id;
            $service->tariff = $tariff;

            $this->newServices[$id] = $service;
        }

        if (null === $service->uploadNodeNumber && null !== $uploadSpeed) {
            $service->uploadSpeed = $uploadSpeed;
            $service->uploadNodeNumber = $this->addServiceCreateCommands(
                $id,
                $tariff->uploadNodeNumber,
                $uploadSpeed,
                Config::DIRECTION_UPLOAD
            );
        }

        if (null === $service->downloadNodeNumber && null !== $downloadSpeed) {
            $service->downloadSpeed = $downloadSpeed;
            $service->downloadNodeNumber = $this->addServiceCreateCommands(
                $id,
                $tariff->downloadNodeNumber,
                $downloadSpeed,
                Config::DIRECTION_DOWNLOAD
            );
        }

        return $service;
    }

    private function addServiceCreateCommands(
        int $serviceId,
        int $tariffNodeNumber,
        float $speed,
        string $direction
    ): int {
        $nodeNumber = $this->config->getNextQueueNodeNumber($this->maxQueueNodeNumber);

        $this->addSetCommand(sprintf('leaf queue %d', $nodeNumber));
        $this->addSetCommand(sprintf('leaf queue %d bandwidth %smbit', $nodeNumber, $speed));
        $this->addSetCommand(sprintf('leaf queue %d parent %d', $nodeNumber, $tariffNodeNumber));
        $this->addSetCommand(sprintf('leaf queue %d queue-type %s_FQ_CODEL', $nodeNumber, Config::UCRM_IDENTIFIER));
        $this->addSetCommand(
            sprintf(
                'leaf queue %d description %s_service_%d_%s',
                $nodeNumber,
                Config::UCRM_IDENTIFIER,
                $serviceId,
                $direction
            )
        );

        return $nodeNumber;
    }

    private function createIp(string $ipString, ConfigItemService $service): ConfigItemIp
    {
        $ip = $this->newIps[$ipString] ?? null;
        if (! $ip) {
            $ip = new ConfigItemIp();
            $ip->ip = $ipString;
            $ip->service = $service;

            $this->newIps[$ipString] = $ip;
        }

        if (null === $ip->uploadNodeNumber && null !== $service->uploadNodeNumber) {
            $ip->uploadNodeNumber = $this->addIpCreateCommands(
                $ipString,
                $service->uploadNodeNumber,
                Config::DIRECTION_UPLOAD
            );
        }

        if (null === $ip->downloadNodeNumber && null !== $service->downloadNodeNumber) {
            $ip->downloadNodeNumber = $this->addIpCreateCommands(
                $ipString,
                $service->downloadNodeNumber,
                Config::DIRECTION_DOWNLOAD
            );
        }

        return $ip;
    }

    private function addIpCreateCommands(string $ip, int $serviceNodeNumber, string $direction): int
    {
        $nodeNumber = $this->config->getNextMatchNodeNumber();

        $this->addSetCommand(sprintf('filters match %d', $nodeNumber));
        $this->addSetCommand(
            sprintf('filters match %d attach-to %d', $nodeNumber, $this->maxQueueNodeNumber)
        );
        $this->addSetCommand(sprintf('filters match %d description %s', $nodeNumber, Config::UCRM_IDENTIFIER));
        $this->addSetCommand(sprintf('filters match %d target %d', $nodeNumber, $serviceNodeNumber));

        switch ($direction) {
            case Config::DIRECTION_UPLOAD:
                $this->addSetCommand(sprintf('filters match %d ip source address %s', $nodeNumber, $ip));
                break;
            case Config::DIRECTION_DOWNLOAD:
                $this->addSetCommand(sprintf('filters match %d ip destination address %s', $nodeNumber, $ip));
                break;
            default:
                throw new \InvalidArgumentException();
        }

        return $nodeNumber;
    }

    private function addTariffsRemoveCommands()
    {
        $tariffsToRemove = array_diff_key(
            $this->config->getTariffs(),
            $this->tariffs
        );

        /** @var ConfigItemTariff $tariff */
        foreach ($tariffsToRemove as $tariff) {
            if (null !== $tariff->uploadNodeNumber) {
                $this->addDeleteCommand(sprintf('branch queue %d', $tariff->uploadNodeNumber));
            }

            if (null !== $tariff->downloadNodeNumber) {
                $this->addDeleteCommand(sprintf('branch queue %d', $tariff->downloadNodeNumber));
            }
        }
    }

    private function addServicesRemoveCommands()
    {
        $servicesToRemove = array_diff_key(
            $this->config->getServices(),
            $this->services
        );

        /** @var ConfigItemService $service */
        foreach ($servicesToRemove as $service) {
            if (null !== $service->uploadNodeNumber) {
                $this->addDeleteCommand(sprintf('leaf queue %d', $service->uploadNodeNumber));
            }

            if (null !== $service->downloadNodeNumber) {
                $this->addDeleteCommand(sprintf('leaf queue %d', $service->downloadNodeNumber));
            }
        }
    }

    private function addIpsRemoveCommands()
    {
        $ipsToRemove = array_diff_key(
            $this->config->getIps(),
            $this->ips
        );

        /** @var ConfigItemIp $ip */
        foreach ($ipsToRemove as $ip) {
            if (null !== $ip->uploadNodeNumber) {
                $this->addDeleteCommand(sprintf('filters match %d', $ip->uploadNodeNumber));
            }

            if (null !== $ip->downloadNodeNumber) {
                $this->addDeleteCommand(sprintf('filters match %d', $ip->downloadNodeNumber));
            }
        }
    }

    private function addTariffsSpeedSetCommands()
    {
        $tariffsUploadSpeed = $tariffsDownloadSpeed = array_fill_keys(array_keys($this->tariffs), 0);
        foreach ($this->services as $service) {
            $tariffsUploadSpeed[$service->tariff->id] += $service->uploadSpeed ?? 0;
            $tariffsDownloadSpeed[$service->tariff->id] += $service->downloadSpeed ?? 0;
        }

        foreach ($this->tariffs as $tariff) {
            if ($tariffsUploadSpeed[$tariff->id] && $tariffsUploadSpeed[$tariff->id] !== $tariff->uploadSpeed) {
                $tariff->uploadSpeed = $tariffsUploadSpeed[$tariff->id];
                $this->addSetCommand(
                    sprintf(
                        'branch queue %d bandwidth %smbit',
                        $tariff->uploadNodeNumber,
                        $tariffsUploadSpeed[$tariff->id]
                    )
                );
            }

            if ($tariffsDownloadSpeed[$tariff->id] && $tariffsDownloadSpeed[$tariff->id] !== $tariff->downloadSpeed) {
                $tariff->downloadSpeed = $tariffsDownloadSpeed[$tariff->id];
                $this->addSetCommand(
                    sprintf(
                        'branch queue %d bandwidth %smbit',
                        $tariff->downloadNodeNumber,
                        $tariffsDownloadSpeed[$tariff->id]
                    )
                );
            }
        }
    }

    private function addSetCommand(string $command)
    {
        $this->addCommand('set', $command);
    }

    private function addDeleteCommand(string $command)
    {
        $this->addCommand('delete', $command);
    }

    private function addCommand(string $action, string $command)
    {
        $command = sprintf('%s traffic-control advanced-queue %s', $action, $command);
        $groupId = $commandId = md5($command);

        foreach (self::GROUP_PATTERNS as $pattern) {
            if ($matches = Strings::match($command, $pattern)) {
                $groupId = md5($matches[1]);

                break;
            }
        }

        $this->commands[$groupId][$commandId] = $command;
    }
}
