<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Event\Job;

use AppBundle\Entity\WebhookEvent;
use SchedulingBundle\Entity\Job;

final class JobEditEvent extends AbstractJobEvent
{
    /**
     * @var Job
     */
    private $jobBeforeEdit;

    public function __construct(Job $job, Job $jobBeforeEdit)
    {
        parent::__construct($job);

        $this->jobBeforeEdit = $jobBeforeEdit;
    }

    public function getJobBeforeEdit(): Job
    {
        return $this->jobBeforeEdit;
    }

    /**
     * @return Job
     */
    public function getWebhookEntityBeforeEdit(): ?object
    {
        return $this->getJobBeforeEdit();
    }

    public function getWebhookChangeType(): string
    {
        return WebhookEvent::EDIT;
    }

    public function getEventName(): string
    {
        return 'job.edit';
    }
}
