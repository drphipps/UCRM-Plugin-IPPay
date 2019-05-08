<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Subscriber\Job;

use Ds\Queue;
use SchedulingBundle\Entity\JobAttachment;
use SchedulingBundle\Event\Job\JobDeleteEvent;
use SchedulingBundle\Event\JobAttachment\JobAttachmentDeleteEvent;
use SchedulingBundle\FileManager\JobAttachmentFileManager;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class DeleteFileWhenJobAttachmentIsRemovedSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var JobAttachmentFileManager
     */
    private $fileManager;

    /**
     * @var Queue|JobAttachment[]
     */
    private $jobAttachments;

    public function __construct(JobAttachmentFileManager $fileManager)
    {
        $this->fileManager = $fileManager;
        $this->jobAttachments = new Queue();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            JobDeleteEvent::class => 'handleJobDeleteEvent',
            JobAttachmentDeleteEvent::class => 'handleJobAttachmentDeleteEvent',
        ];
    }

    public function handleJobAttachmentDeleteEvent(JobAttachmentDeleteEvent $event): void
    {
        $this->jobAttachments->push($event->getJobAttachment());
    }

    public function handleJobDeleteEvent(JobDeleteEvent $event): void
    {
        foreach ($event->getJob()->getAttachments() as $jobAttachment) {
            $this->jobAttachments->push($jobAttachment);
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
        foreach ($this->jobAttachments as $jobAttachment) {
            $this->fileManager->handleAttachmentDelete($jobAttachment);
        }
    }

    public function rollback(): void
    {
        $this->jobAttachments->clear();
    }
}
