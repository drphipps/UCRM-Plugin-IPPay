<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Client;

use AppBundle\Entity\Client;
use AppBundle\Entity\WebhookEvent;

final class ClientDeleteEvent extends AbstractClientEvent
{
    /**
     * @var int
     */
    private $id;

    public function __construct(Client $client, int $id)
    {
        parent::__construct($client);
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getWebhookChangeType(): string
    {
        return WebhookEvent::DELETE;
    }

    public function getWebhookEntityId(): int
    {
        return $this->id;
    }

    public function getEventName(): string
    {
        return 'client.delete';
    }
}
