<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Event\Job;

use AppBundle\Interfaces\WebhookRequestableInterface;
use SchedulingBundle\Entity\Job;
use Symfony\Component\EventDispatcher\Event;

abstract class AbstractJobEvent extends Event implements WebhookRequestableInterface
{
    /**
     * @var Job
     */
    protected $job;

    public function __construct(Job $job)
    {
        $this->job = $job;
    }

    public function getJob(): Job
    {
        return $this->job;
    }

    public function getWebhookEntityClass(): string
    {
        return 'job';
    }

    /**
     * @return Job
     */
    public function getWebhookEntity(): ?object
    {
        return $this->job;
    }

    /**
     * @return Job|null
     */
    public function getWebhookEntityBeforeEdit(): ?object
    {
        return null;
    }

    public function getWebhookEntityId(): ?int
    {
        return $this->job->getId();
    }
}
