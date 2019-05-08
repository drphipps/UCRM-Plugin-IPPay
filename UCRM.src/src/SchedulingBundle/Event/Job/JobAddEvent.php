<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Event\Job;

use AppBundle\Entity\WebhookEvent;

final class JobAddEvent extends AbstractJobEvent
{
    public function getWebhookChangeType(): string
    {
        return WebhookEvent::INSERT;
    }

    public function getEventName(): string
    {
        return 'job.add';
    }
}
