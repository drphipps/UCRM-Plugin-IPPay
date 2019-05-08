<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Service;

use AppBundle\Entity\WebhookEvent;

final class ServiceEndEvent extends AbstractServiceEvent
{
    public function getWebhookChangeType(): string
    {
        return WebhookEvent::END;
    }

    public function getEventName(): string
    {
        return 'service.end';
    }
}
