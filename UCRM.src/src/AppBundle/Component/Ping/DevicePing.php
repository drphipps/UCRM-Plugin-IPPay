<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Ping;

class DevicePing
{
    const DEVICE_NEXT_PING = 300; // min amount of seconds between pings if errorCount = 0
    const DEVICE_NEXT_PING_ERR = 60; // min amount of seconds between pings if errorCount > 0

    const TYPE_NETWORK = 0;
    const TYPE_SERVICE = 1;

    /**
     * @var int
     */
    private $deviceId;

    /**
     * @var int
     */
    private $type;

    /**
     * @var string
     */
    private $ipAddress;

    /**
     * @var int
     */
    private $errorCount = 0;

    /**
     * @var int timestamp
     */
    private $lastPing = 0;

    /**
     * @var float
     */
    private $latency = 0.0;

    /**
     * @var float
     */
    private $packetLoss = 0.0;

    /**
     * @var bool
     */
    private $down = false;

    /**
     * @var bool
     */
    private $createStatistics = true;

    public function __construct(
        int $deviceId,
        int $type,
        string $ipAddress,
        int $errorCount,
        bool $createStatistics = true
    ) {
        $this->deviceId = $deviceId;
        $this->type = $type;
        $this->ipAddress = $ipAddress;
        $this->errorCount = $errorCount;
        $this->createStatistics = $createStatistics;
    }

    public function getDeviceId(): int
    {
        return $this->deviceId;
    }

    /**
     * @return $this
     */
    public function setDeviceId(int $deviceId)
    {
        $this->deviceId = $deviceId;

        return $this;
    }

    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return $this
     */
    public function setType(int $type)
    {
        $this->type = $type;

        return $this;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    /**
     * @return $this
     */
    public function setIpAddress(string $ipAddress)
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    /**
     * @return $this
     */
    public function setErrorCount(int $errorCount)
    {
        $this->errorCount = $errorCount;

        return $this;
    }

    public function getLastPing(): int
    {
        return $this->lastPing;
    }

    /**
     * @return $this
     */
    public function setLastPing(int $lastPing)
    {
        $this->lastPing = $lastPing;

        return $this;
    }

    public function getLatency(): float
    {
        return $this->latency;
    }

    /**
     * @return $this
     */
    public function setLatency(float $latency)
    {
        $this->latency = $latency;

        return $this;
    }

    public function getPacketLoss(): float
    {
        return $this->packetLoss;
    }

    /**
     * @return $this
     */
    public function setPacketLoss(float $packetLoss)
    {
        $this->packetLoss = $packetLoss;

        return $this;
    }

    public function isDown(): bool
    {
        return $this->down;
    }

    /**
     * @return $this
     */
    public function setDown(bool $down)
    {
        $this->down = $down;

        return $this;
    }

    public function createStatistics(): bool
    {
        return $this->createStatistics;
    }

    /**
     * @return $this
     */
    public function setCreateStatistics(bool $createStatistics)
    {
        $this->createStatistics = $createStatistics;

        return $this;
    }

    /**
     * @param int $timestamp
     */
    public function canBePinged(int $timestamp = null): bool
    {
        if (0 === $this->lastPing) {
            return true;
        }

        $timestamp = $timestamp ?? time();
        $diff = $timestamp - $this->lastPing;
        $nextAttempt = ($this->errorCount > 0 ? self::DEVICE_NEXT_PING_ERR : self::DEVICE_NEXT_PING) - $diff;

        return $nextAttempt <= 0;
    }
}
