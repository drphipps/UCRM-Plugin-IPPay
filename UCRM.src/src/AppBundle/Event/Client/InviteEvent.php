<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Client;

use AppBundle\Entity\WebhookEvent;

class InviteEvent extends AbstractClientEvent
{
    public function getWebhookChangeType(): string
    {
        return WebhookEvent::INVITATION;
    }

    public function getEventName(): string
    {
        return 'client.invite';
    }
}
