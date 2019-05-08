<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Interfaces;

interface WebhookRequestableInterface
{
    public function getWebhookEntityClass(): string;

    /**
     * @return object entity on which the webhook was triggered
     */
    public function getWebhookEntity(): ?object;

    /**
     * @return object|null previous state of entity on which the webhook was triggered
     */
    public function getWebhookEntityBeforeEdit(): ?object;

    public function getWebhookChangeType(): string;

    public function getWebhookEntityId(): ?int;

    public function getEventName(): string;
}
