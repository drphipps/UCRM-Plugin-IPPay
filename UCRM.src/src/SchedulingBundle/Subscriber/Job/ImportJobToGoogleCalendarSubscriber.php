<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Subscriber\Job;

use RabbitMqBundle\RabbitMqEnqueuer;
use SchedulingBundle\Event\Job\JobAddEvent;
use SchedulingBundle\Event\Job\JobEditEvent;
use SchedulingBundle\RabbitMq\SynchronizeJobToGoogleCalendarMessage;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class ImportJobToGoogleCalendarSubscriber implements TransactionEventSubscriberInterface
{
    use RabbitMessagesSubscriberTrait;

    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    public function __construct(RabbitMqEnqueuer $rabbitMqEnqueuer)
    {
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            JobAddEvent::class => 'handleJobAddEvent',
            JobEditEvent::class => 'handleJobEditEvent',
        ];
    }

    public function handleJobAddEvent(JobAddEvent $event): void
    {
        $job = $event->getJob();
        if ($job->getAssignedUser() && $job->getAssignedUser()->isGoogleCalendarSynchronizationPossible()) {
            $this->rabbitMessages[] = new SynchronizeJobToGoogleCalendarMessage(
                $job,
                SynchronizeJobToGoogleCalendarMessage::TYPE_CREATE
            );
        }
    }

    public function handleJobEditEvent(JobEditEvent $event): void
    {
        $job = $event->getJob();
        $jobBeforeEdit = $event->getJobBeforeEdit();

        // If user is changed, create event in new users calendar.
        if (
            $jobBeforeEdit->getAssignedUser() !== $job->getAssignedUser()
            && $job->getAssignedUser()
            && $job->getAssignedUser()->isGoogleCalendarSynchronizationPossible()
        ) {
            $this->rabbitMessages[] = new SynchronizeJobToGoogleCalendarMessage(
                $job,
                SynchronizeJobToGoogleCalendarMessage::TYPE_CREATE
            );
        }
    }
}
