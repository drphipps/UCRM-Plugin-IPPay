<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Service;

use AppBundle\Entity\Option;
use AppBundle\Entity\ServiceStopReason;
use AppBundle\Event\Service\ServiceSuspendEvent;
use AppBundle\Service\Options;
use AppBundle\Service\SuspensionEmailSender;
use Ds\Queue;
use Psr\Log\LoggerInterface;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class ServiceSuspendEmailSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var Queue|ServiceSuspendEvent[]
     */
    private $suspendEvents;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SuspensionEmailSender
     */
    private $suspensionEmailSender;

    public function __construct(
        Options $options,
        SuspensionEmailSender $suspensionEmailSender,
        LoggerInterface $logger
    ) {
        $this->options = $options;
        $this->suspensionEmailSender = $suspensionEmailSender;
        $this->logger = $logger;
        $this->suspendEvents = new Queue();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ServiceSuspendEvent::class => 'handleServiceSuspendEvent',
        ];
    }

    public function handleServiceSuspendEvent(ServiceSuspendEvent $event): void
    {
        $sendSuspendEmails = $this->options->get(Option::NOTIFICATION_SERVICE_SUSPENDED);
        if (! $sendSuspendEmails) {
            $this->logger->info('Suspend notifications are disabled.');
        } elseif ($this->isSuspendEventApplicable($event)) {
            $this->suspendEvents->push($event);
            $this->logger->info('Suspend email added to queue.');
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
        foreach ($this->suspendEvents as $event) {
            $this->suspensionEmailSender->send($event->getService(), $event->getService()->getSuspendedByInvoices()->toArray());
        }
    }

    public function rollback(): void
    {
        $this->suspendEvents->clear();
    }

    private function isSuspendEventApplicable(ServiceSuspendEvent $event): bool
    {
        return ! $event->getServiceBeforeUpdate()->getSuspendedFrom()
            && $event->getService()->getSuspendedFrom()
            && $event->getService()->getStopReason()
            && $event->getService()->getStopReason()->getId() === ServiceStopReason::STOP_REASON_OVERDUE_ID;
    }
}
