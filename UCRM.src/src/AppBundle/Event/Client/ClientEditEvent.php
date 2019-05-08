<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Client;

use AppBundle\Entity\Client;
use AppBundle\Entity\WebhookEvent;

final class ClientEditEvent extends AbstractClientEvent
{
    /**
     * @var Client
     */
    private $clientBeforeUpdate;

    public function __construct(Client $client, Client $clientBeforeUpdate)
    {
        parent::__construct($client);
        $this->clientBeforeUpdate = $clientBeforeUpdate;
    }

    public function getClientBeforeUpdate(): Client
    {
        return $this->clientBeforeUpdate;
    }

    /**
     * @return Client
     */
    public function getWebhookEntityBeforeEdit(): ?object
    {
        return $this->getClientBeforeUpdate();
    }

    public function getWebhookChangeType(): string
    {
        return WebhookEvent::EDIT;
    }

    public function getEventName(): string
    {
        return 'client.edit';
    }
}
