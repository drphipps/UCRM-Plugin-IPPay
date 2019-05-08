<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Client;

use AppBundle\Entity\WebhookEvent;
use AppBundle\Interfaces\WebhookRequestableInterface;
use Symfony\Component\EventDispatcher\Event;

final class TestEvent extends Event implements WebhookRequestableInterface
{
    /**
     * @var int|null
     */
    private $id;

    public function getWebhookEntityClass(): string
    {
        return 'webhook';
    }

    public function __construct(?int $id)
    {
        $this->id = $id;
    }

    public function getWebhookEntity(): ?object
    {
        return null;
    }

    public function getWebhookChangeType(): string
    {
        return WebhookEvent::TEST;
    }

    public function getWebhookEntityId(): ?int
    {
        return $this->id;
    }

    public function getWebhookEntityBeforeEdit(): ?object
    {
        return null;
    }

    public function getEventName(): string
    {
        return 'test';
    }
}
