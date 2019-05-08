<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Client;

use RabbitMqBundle\MessageInterface;

class SendInvitationEmailsMessage implements MessageInterface
{
    public function getProducer(): string
    {
        return 'send_invitation_emails';
    }

    public function getBody(): string
    {
        return '[]';
    }

    public function getBodyProperties(): array
    {
        return [];
    }

    public function getRoutingKey(): string
    {
        return 'send_invitation_emails';
    }

    public function getProperties(): array
    {
        return [];
    }
}
