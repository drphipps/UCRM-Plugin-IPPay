<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Webhook;

use RabbitMqBundle\MessageInterface;

class WebhookEventRequestMessage implements MessageInterface
{
    /**
     * @var string
     */
    private $json;

    public function __construct(string $dataJson)
    {
        $this->json = $dataJson;
    }

    public function getProducer(): string
    {
        return 'webhook_event_request';
    }

    public function getBody(): string
    {
        return $this->json;
    }

    public function getBodyProperties(): array
    {
        return [
            'eventName',
            'entityClass',
            'changeType',
            'entityId',
            'extraData',
        ];
    }

    public function getRoutingKey(): string
    {
        return 'webhook_event_request';
    }

    public function getProperties(): array
    {
        return [];
    }
}
