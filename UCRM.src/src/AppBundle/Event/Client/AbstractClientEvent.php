<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Client;

use AppBundle\Entity\Client;
use AppBundle\Entity\WebhookEvent;
use AppBundle\Interfaces\WebhookRequestableInterface;
use Symfony\Component\EventDispatcher\Event;

abstract class AbstractClientEvent extends Event implements WebhookRequestableInterface
{
    /**
     * @var Client
     */
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getWebhookEntityClass(): string
    {
        return 'client';
    }

    /**
     * @return Client
     */
    public function getWebhookEntity(): ?object
    {
        return $this->client;
    }

    /**
     * @return Client|null
     */
    public function getWebhookEntityBeforeEdit(): ?object
    {
        return null;
    }

    public function getWebhookChangeType(): string
    {
        return WebhookEvent::NOTIFICATION;
    }

    public function getWebhookEntityId(): ?int
    {
        return $this->getWebhookEntity()->getId();
    }
}
