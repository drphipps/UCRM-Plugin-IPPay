<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Subscriber\Job;

use RabbitMqBundle\RabbitMqEnqueuer;
use SchedulingBundle\Event\Job\JobDeleteEvent;
use SchedulingBundle\Event\Job\JobEditEvent;
use SchedulingBundle\RabbitMq\SynchronizeJobToGoogleCalendarMessage;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class DeleteJobFromGoogleCalendarSubscriber implements TransactionEventSubscriberInterface
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
            JobDeleteEvent::class => 'handleJobDeleteEvent',
            JobEditEvent::class => 'handleJobEditEvent',
        ];
    }

    public function handleJobDeleteEvent(JobDeleteEvent $event): void
    {
        $job = $event->getJob();
        if ($job->getAssignedUser() && $job->getAssignedUser()->isGoogleCalendarSynchronizationPossible()) {
            $this->rabbitMessages[] = new SynchronizeJobToGoogleCalendarMessage(
                $job,
                SynchronizeJobToGoogleCalendarMessage::TYPE_DELETE
            );
        }
    }

    public function handleJobEditEvent(JobEditEvent $event): void
    {
        $job = $event->getJob();
        $jobBeforeEdit = $event->getJobBeforeEdit();

        // If user is changed, delete event from old users calendar.
        if (
            $jobBeforeEdit->getAssignedUser() !== $job->getAssignedUser()
            && $jobBeforeEdit->getAssignedUser()
            && $jobBeforeEdit->getAssignedUser()->isGoogleCalendarSynchronizationPossible()
        ) {
            $this->rabbitMessages[] = new SynchronizeJobToGoogleCalendarMessage(
                $jobBeforeEdit,
                SynchronizeJobToGoogleCalendarMessage::TYPE_DELETE
            );
        }
    }
}
