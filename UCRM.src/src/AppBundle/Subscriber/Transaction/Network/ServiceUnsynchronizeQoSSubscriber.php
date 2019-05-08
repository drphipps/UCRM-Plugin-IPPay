<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Network;

use AppBundle\Component\QoS\QoSSynchronizationDetector;
use AppBundle\Component\QoS\QoSSynchronizationManager;
use AppBundle\Entity\Option;
use AppBundle\Entity\Service;
use AppBundle\Event\Service\ServiceAddEvent;
use AppBundle\Event\Service\ServiceEditEvent;
use AppBundle\Service\Options;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class ServiceUnsynchronizeQoSSubscriber implements TransactionEventSubscriberInterface
{
    const QOS_SYNC_SERVICE_STATUSES = [
        Service::STATUS_ACTIVE,
        Service::STATUS_SUSPENDED,
        // Prepared services are intentionally not ignored. The technician who sets it up may want to test it.
        Service::STATUS_PREPARED,
        Service::STATUS_PREPARED_BLOCKED,
    ];

    /**
     * @var Options
     */
    private $options;

    /**
     * @var QoSSynchronizationManager
     */
    private $synchronizationManager;

    /**
     * @var QoSSynchronizationDetector
     */
    private $qosSynchronizationDetector;

    /**
     * @var bool
     */
    private $markAllGatewaysUnsynchronized = false;

    public function __construct(
        Options $options,
        QoSSynchronizationManager $synchronizationManager,
        QoSSynchronizationDetector $qosSynchronizationDetector
    ) {
        $this->options = $options;
        $this->synchronizationManager = $synchronizationManager;
        $this->qosSynchronizationDetector = $qosSynchronizationDetector;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ServiceAddEvent::class => 'handleServiceAddEvent',
            ServiceEditEvent::class => 'handleServiceEditEvent',
        ];
    }

    public function handleServiceAddEvent(ServiceAddEvent $event): void
    {
        if (! $this->options->get(Option::QOS_ENABLED)) {
            return;
        }

        $service = $event->getService();

        if (! in_array($service->getStatus(), self::QOS_SYNC_SERVICE_STATUSES, true)) {
            return;
        }

        $this->unsynchronizeServiceDevices($service);
    }

    public function handleServiceEditEvent(ServiceEditEvent $event): void
    {
        if (! $this->options->get(Option::QOS_ENABLED)) {
            return;
        }

        $service = $event->getService();
        $serviceBeforeUpdate = $event->getServiceBeforeUpdate();

        $service->calculateStatus();

        if (
            $serviceBeforeUpdate->getStatus() !== Service::STATUS_ENDED
            && $service->getStatus() === Service::STATUS_ENDED
        ) {
            $this->unsynchronizeServiceDevices($service);

            return;
        }

        if (
            ! in_array($service->getStatus(), self::QOS_SYNC_SERVICE_STATUSES, true)
            || $service->getTariff() === $serviceBeforeUpdate->getTariff()
        ) {
            return;
        }

        $this->unsynchronizeServiceDevices($service);
    }

    private function unsynchronizeServiceDevices(Service $service): void
    {
        foreach ($service->getServiceDevices() as $device) {
            $synchronizationType = $this->qosSynchronizationDetector->getSynchronizationType(
                $device,
                $this->options->get(Option::QOS_DESTINATION)
            );

            switch ($synchronizationType) {
                case QoSSynchronizationDetector::UNSYNC_GATEWAYS:
                    $this->markAllGatewaysUnsynchronized = true;
                    break;
                case QoSSynchronizationDetector::UNSYNC_PARENTS:
                    // @todo Refactor to a single recursive query and run it in handleFlushEvent.
                    $this->synchronizationManager->markTopParentsUnsynchronized($device);
                    break;
            }
        }
    }

    public function preFlush(): void
    {
        if ($this->markAllGatewaysUnsynchronized) {
            $this->synchronizationManager->markAllGatewaysUnsynchronized();

            $this->markAllGatewaysUnsynchronized = false;
        }
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
    }

    public function rollback(): void
    {
        $this->markAllGatewaysUnsynchronized = false;
    }
}
