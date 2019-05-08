<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Event\Job;

use AppBundle\Entity\WebhookEvent;

final class JobDeleteEvent extends AbstractJobEvent
{
    public function getWebhookChangeType(): string
    {
        return WebhookEvent::DELETE;
    }

    public function getEventName(): string
    {
        return 'job.delete';
    }
}
