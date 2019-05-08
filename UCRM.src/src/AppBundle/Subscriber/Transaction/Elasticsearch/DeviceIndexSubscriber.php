<?php
/*
 * @copyright Copyright (c) 2016 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Elasticsearch;

use AppBundle\Entity\Device;
use AppBundle\Event\Device\DeviceUpdateIndexEvent;
use AppBundle\Event\DeviceInterface\DeviceInterfaceAddEvent;
use AppBundle\Event\DeviceInterface\DeviceInterfaceArchiveEvent;
use AppBundle\Event\DeviceInterface\DeviceInterfaceEditEvent;
use AppBundle\Event\DeviceInterfaceIp\DeviceInterfaceIpAddEvent;
use AppBundle\Event\DeviceInterfaceIp\DeviceInterfaceIpDeleteEvent;
use FOS\ElasticaBundle\Persister\ObjectPersisterInterface;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class DeviceIndexSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var ObjectPersisterInterface
     */
    private $objectPersister;

    /**
     * @var Device[]
     */
    private $devicesToUpdate = [];

    public function __construct(ObjectPersisterInterface $objectPersister)
    {
        $this->objectPersister = $objectPersister;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DeviceUpdateIndexEvent::class => 'handleDeviceUpdateIndexEvent',
            DeviceInterfaceAddEvent::class => 'handleDeviceInterfaceAddEvent',
            DeviceInterfaceEditEvent::class => 'handleDeviceInterfaceEditEvent',
            DeviceInterfaceArchiveEvent::class => 'handleDeviceInterfaceArchiveEvent',
            DeviceInterfaceIpAddEvent::class => 'handleDeviceInterfaceIpAddEvent',
            DeviceInterfaceIpDeleteEvent::class => 'handleDeviceInterfaceIpDeleteEvent',
        ];
    }

    public function handleDeviceUpdateIndexEvent(DeviceUpdateIndexEvent $event): void
    {
        $this->devicesToUpdate[$event->getDevice()->getId()] = $event->getDevice();
    }

    public function handleDeviceInterfaceAddEvent(DeviceInterfaceAddEvent $event): void
    {
        if ($device = $event->getDeviceInterface()->getDevice()) {
            $this->devicesToUpdate[$device->getId()] = $device;
        }
    }

    public function handleDeviceInterfaceEditEvent(DeviceInterfaceEditEvent $event): void
    {
        if ($device = $event->getDeviceInterface()->getDevice()) {
            $this->devicesToUpdate[$device->getId()] = $device;
        }
    }

    public function handleDeviceInterfaceArchiveEvent(DeviceInterfaceArchiveEvent $event): void
    {
        if ($device = $event->getDeviceInterface()->getDevice()) {
            $this->devicesToUpdate[$device->getId()] = $device;
        }
    }

    public function handleDeviceInterfaceIpAddEvent(DeviceInterfaceIpAddEvent $event): void
    {
        if ($device = $event->getDeviceInterfaceIp()->getInterface()->getDevice()) {
            $this->devicesToUpdate[$device->getId()] = $device;
        }
    }

    public function handleDeviceInterfaceIpDeleteEvent(DeviceInterfaceIpDeleteEvent $event): void
    {
        if ($device = $event->getDeviceInterfaceIp()->getInterface()->getDevice()) {
            $this->devicesToUpdate[$device->getId()] = $device;
        }
    }

    public function preFlush(): void
    {
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
        if ($this->devicesToUpdate) {
            $this->objectPersister->replaceMany($this->devicesToUpdate);
            $this->devicesToUpdate = [];
        }
    }

    public function rollback(): void
    {
        $this->devicesToUpdate = [];
    }
}
