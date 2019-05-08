<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\GoogleCalendar;

use SchedulingBundle\Entity\Job;

class Operation
{
    public const UUID_PREFIX = 'UBNT/UCRM/';

    public const TYPE_IMPORT = 'import';
    public const TYPE_UPDATE = 'update';
    public const TYPE_DELETE = 'delete';

    public const EVENT_STATUS_CANCELLED = 'cancelled';
    public const EVENT_STATUS_CONFIRMED = 'confirmed';

    /**
     * @var string
     */
    private $type;

    /**
     * @var Job|null
     */
    private $job;

    /**
     * @var \Google_Service_Calendar_Event|null
     */
    private $event;

    public function __construct(string $type, ?Job $job, ?\Google_Service_Calendar_Event $event)
    {
        $this->type = $type;
        $this->job = $job;
        $this->event = $event;

        if (! $job && ! $event) {
            throw new \InvalidArgumentException('At least 1 argument is required.');
        }
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getJob(): ?Job
    {
        return $this->job;
    }

    public function getEvent(): ?\Google_Service_Calendar_Event
    {
        return $this->event;
    }
}
