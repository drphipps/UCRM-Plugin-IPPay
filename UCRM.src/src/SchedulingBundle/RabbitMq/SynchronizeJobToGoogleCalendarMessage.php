<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\RabbitMq;

use Nette\Utils\Json;
use RabbitMqBundle\MessageInterface;
use SchedulingBundle\Entity\Job;

class SynchronizeJobToGoogleCalendarMessage implements MessageInterface
{
    public const TYPE_CREATE = 'create';
    public const TYPE_UPDATE = 'update';
    public const TYPE_DELETE = 'delete';

    /**
     * @var Job
     */
    private $job;

    /**
     * @var string
     */
    private $type;

    public function __construct(Job $job, string $type)
    {
        $this->job = $job;
        $this->type = $type;

        if (! $this->job->getAssignedUser()) {
            throw new \InvalidArgumentException(
                'Only jobs with assigned users can be synchronized to Google Calendar.'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getProducer(): string
    {
        return 'synchronize_job_to_google_calendar';
    }

    /**
     * {@inheritdoc}
     */
    public function getBody(): string
    {
        return Json::encode(
            [
                'type' => $this->type,
                'job' => [
                    'id' => $this->job->getId(),
                    'uuid' => $this->job->getUuid(),
                    'user_id' => $this->job->getAssignedUser()->getId(),
                ],
            ]
        );
    }

    public function getBodyProperties(): array
    {
        return [
            'type',
            'job' => [
                'id',
                'uuid',
                'user_id',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutingKey(): string
    {
        return 'synchronize_job_to_google_calendar';
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties(): array
    {
        return [];
    }
}
