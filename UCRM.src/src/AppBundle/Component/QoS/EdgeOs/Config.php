<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\QoS\EdgeOs;

use AppBundle\Util\VyattaParser;
use Nette\Utils\Strings;

class Config
{
    const MAX_QUEUE_NODE_NUMBER = 1023;
    const MAX_QUEUE_NODE_NUMBER_EXTENDED = 32767;
    const MAX_MATCH_NODE_NUMBER = 65535;

    const DEFAULT_ROOT_BANDWIDTH = 1000;

    const UCRM_IDENTIFIER = 'UCRM';

    const DIRECTION_UPLOAD = 'upload';
    const DIRECTION_DOWNLOAD = 'download';

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
     * @var array
     */
    private $queueNodeRange = [];

    /**
     * @var array
     */
    private $matchNodeRange = [];

    /**
     * @var int
     */
    private $rootNodeNumber;

    /**
     * @var float
     */
    private $rootBandwidth;

    /**
     * @var bool
     */
    private $isRootBandwidthChangeable = true;

    /**
     * @var bool
     */
    private $hasRoot = false;

    /**
     * @var bool
     */
    private $hasQueueType = false;

    public function __construct(string $config)
    {
        $this->init($config);
    }

    /**
     * @return ConfigItemTariff|null
     */
    public function getTariff(int $tariffId)
    {
        return $this->tariffs[$tariffId] ?? null;
    }

    /**
     * @return array|ConfigItemTariff[]
     */
    public function getTariffs(): array
    {
        return $this->tariffs;
    }

    /**
     * @return ConfigItemService|null
     */
    public function getService(int $serviceId)
    {
        return $this->services[$serviceId] ?? null;
    }

    /**
     * @return array|ConfigItemService[]
     */
    public function getServices(): array
    {
        return $this->services;
    }

    /**
     * @return ConfigItemIp|null
     */
    public function getIp(string $ip)
    {
        return $this->ips[$ip] ?? null;
    }

    /**
     * @return array|ConfigItemIp[]
     */
    public function getIps(): array
    {
        return $this->ips;
    }

    /**
     * @throws \OutOfRangeException
     */
    public function getNextQueueNodeNumber(int $maxQueueNodeNumber): int
    {
        for ($nodeNumber = 1; $nodeNumber <= $maxQueueNodeNumber; ++$nodeNumber) {
            if (! in_array($nodeNumber, $this->queueNodeRange, true)) {
                $this->queueNodeRange[] = $nodeNumber;

                return $nodeNumber;
            }
        }

        throw new \OutOfRangeException('Queue node range is exceeded.');
    }

    public function getNextMatchNodeNumber(): int
    {
        for ($nodeNumber = 1; $nodeNumber <= self::MAX_MATCH_NODE_NUMBER; ++$nodeNumber) {
            if (! in_array($nodeNumber, $this->matchNodeRange, true)) {
                $this->matchNodeRange[] = $nodeNumber;

                return $nodeNumber;
            }
        }

        throw new \OutOfRangeException('Match node range is exceeded.');
    }

    /**
     * @throws \OutOfRangeException
     */
    public function getRootNodeNumber(int $maxQueueNodeNumber): int
    {
        if (null === $this->rootNodeNumber) {
            for ($nodeNumber = $maxQueueNodeNumber; $nodeNumber >= 1; --$nodeNumber) {
                if (! in_array($nodeNumber, $this->queueNodeRange, true)) {
                    $this->rootNodeNumber = $nodeNumber;
                    $this->queueNodeRange[] = $this->rootNodeNumber;

                    return $this->rootNodeNumber;
                }
            }

            throw new \OutOfRangeException('Queue node range is exceeded.');
        }

        return $this->rootNodeNumber;
    }

    public function getRootBandwidth(): float
    {
        return $this->rootBandwidth ?? self::DEFAULT_ROOT_BANDWIDTH;
    }

    public function setRootBandwidth(float $bandwidth)
    {
        $this->rootBandwidth = $bandwidth;
    }

    public function isRootBandwidthChangeable(): bool
    {
        return $this->isRootBandwidthChangeable;
    }

    public function hasRoot(): bool
    {
        return $this->hasRoot;
    }

    public function hasQueueType(): bool
    {
        return $this->hasQueueType;
    }

    private function init(string $config)
    {
        $config = VyattaParser::parse($config);

        if (! array_key_exists('advanced-queue', $config)) {
            return;
        }

        if (array_key_exists('root', $config['advanced-queue'])) {
            foreach ($config['advanced-queue']['root'] as $node => $root) {
                $nodeNumber = $this->extractNodeNumber($node, 'queue_');
                $this->queueNodeRange[] = $nodeNumber;

                $this->hasRoot = true;
                if (null === $this->rootNodeNumber) {
                    $this->rootNodeNumber = $nodeNumber;
                }

                if (array_key_exists('description', $root) && $root['description'] === self::UCRM_IDENTIFIER) {
                    $this->rootNodeNumber = $nodeNumber;
                    $this->rootBandwidth = (float) Strings::substring($root['bandwidth'], 0, -Strings::length('mbit'));
                }
            }
        }

        if (null === $this->rootBandwidth) {
            $this->isRootBandwidthChangeable = false;
        }

        if (array_key_exists('queue-type', $config['advanced-queue'])) {
            foreach ($config['advanced-queue']['queue-type'] as $node => $queueType) {
                if ($node === sprintf('fq-codel_%s_FQ_CODEL', self::UCRM_IDENTIFIER)) {
                    $this->hasQueueType = true;
                    break;
                }
            }
        }

        $tariffsNodeToIdMap = [];
        $servicesNodeToIdMap = [];

        if (array_key_exists('branch', $config['advanced-queue'])) {
            foreach ($config['advanced-queue']['branch'] as $node => $branch) {
                $nodeNumber = $this->extractNodeNumber($node, 'queue_');
                $this->queueNodeRange[] = $nodeNumber;

                $regExp = sprintf(
                    '/^%s_tariff_(\d+)_(%s|%s)$/',
                    self::UCRM_IDENTIFIER,
                    self::DIRECTION_DOWNLOAD,
                    self::DIRECTION_UPLOAD
                );

                if (array_key_exists('description', $branch)
                    && $matches = Strings::match($branch['description'], $regExp)
                ) {
                    $tariffsNodeToIdMap[$nodeNumber] = (int) $matches[1];

                    $this->setTariff((int) $matches[1], $nodeNumber, (float) $branch['bandwidth'], $matches[2]);
                }
            }
        }

        if (array_key_exists('leaf', $config['advanced-queue'])) {
            foreach ($config['advanced-queue']['leaf'] as $node => $leaf) {
                $nodeNumber = $this->extractNodeNumber($node, 'queue_');
                $this->queueNodeRange[] = $nodeNumber;

                $regExp = sprintf(
                    '/^%s_service_(\d+)_(%s|%s)$/',
                    self::UCRM_IDENTIFIER,
                    self::DIRECTION_DOWNLOAD,
                    self::DIRECTION_UPLOAD
                );

                if (array_key_exists('description', $leaf)
                    && $matches = Strings::match($leaf['description'], $regExp)
                ) {
                    $servicesNodeToIdMap[$nodeNumber] = (int) $matches[1];

                    if (array_key_exists($leaf['parent'], $tariffsNodeToIdMap)) {
                        $this->setService(
                            (int) $matches[1],
                            $nodeNumber,
                            (float) $leaf['bandwidth'],
                            $matches[2],
                            $this->tariffs[$tariffsNodeToIdMap[$leaf['parent']]]
                        );
                    }
                }
            }
        }

        if (array_key_exists('filters', $config['advanced-queue'])) {
            foreach ($config['advanced-queue']['filters'] as $node => $filter) {
                $nodeNumber = $this->extractNodeNumber($node, 'match_');
                $this->matchNodeRange[] = $nodeNumber;

                if (array_key_exists('description', $filter) && $filter['description'] === self::UCRM_IDENTIFIER) {
                    foreach ($filter['ip'] as $key => $ip) {
                        $arguments = [
                            $ip['address'],
                            $nodeNumber,
                        ];

                        switch ($key) {
                            case 'source':
                                $arguments[] = self::DIRECTION_UPLOAD;
                                break;
                            case 'destination':
                                $arguments[] = self::DIRECTION_DOWNLOAD;
                                break;
                        }

                        if (array_key_exists($filter['target'], $servicesNodeToIdMap)) {
                            $arguments[] = $this->services[$servicesNodeToIdMap[$filter['target']]];
                            $this->setIp(...$arguments);
                        }
                    }
                }
            }
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function setTariff(int $id, int $nodeNumber, float $speed, string $direction)
    {
        $tariff = $this->tariffs[$id] ?? null;
        if (! $tariff) {
            $tariff = new ConfigItemTariff();
            $tariff->id = $id;

            $this->tariffs[$id] = $tariff;
        }

        switch ($direction) {
            case self::DIRECTION_DOWNLOAD:
                $tariff->downloadSpeed = $speed;
                $tariff->downloadNodeNumber = $nodeNumber;
                break;
            case self::DIRECTION_UPLOAD:
                $tariff->uploadSpeed = $speed;
                $tariff->uploadNodeNumber = $nodeNumber;
                break;
            default:
                throw new \InvalidArgumentException();
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function setService(
        int $id,
        int $nodeNumber,
        float $speed,
        string $direction,
        ConfigItemTariff $tariff
    ) {
        $service = $this->services[$id] ?? null;
        if (! $service) {
            $service = new ConfigItemService();
            $service->id = $id;
            $service->tariff = $tariff;

            $this->services[$id] = $service;
        }

        switch ($direction) {
            case self::DIRECTION_DOWNLOAD:
                $service->downloadSpeed = $speed;
                $service->downloadNodeNumber = $nodeNumber;
                break;
            case self::DIRECTION_UPLOAD:
                $service->uploadSpeed = $speed;
                $service->uploadNodeNumber = $nodeNumber;
                break;
            default:
                throw new \InvalidArgumentException();
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function setIp(
        string $ipString,
        int $nodeNumber,
        string $direction,
        ConfigItemService $service
    ) {
        $ip = $this->ips[$ipString] ?? null;
        if (! $ip) {
            $ip = new ConfigItemIp();
            $ip->ip = $ipString;
            $ip->service = $service;

            $this->ips[$ipString] = $ip;
        }

        switch ($direction) {
            case self::DIRECTION_DOWNLOAD:
                $ip->downloadNodeNumber = $nodeNumber;
                break;
            case self::DIRECTION_UPLOAD:
                $ip->uploadNodeNumber = $nodeNumber;
                break;
            default:
                throw new \InvalidArgumentException();
        }
    }

    private function extractNodeNumber(string $node, string $prefix): int
    {
        return (int) Strings::substring($node, Strings::length($prefix));
    }
}
