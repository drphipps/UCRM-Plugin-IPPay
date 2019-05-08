<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\User;

use AppBundle\Entity\User;
use AppBundle\Entity\WebhookEvent;
use AppBundle\Interfaces\WebhookRequestableInterface;
use Symfony\Component\EventDispatcher\Event;

final class ResetPasswordEvent extends Event implements WebhookRequestableInterface
{
    /**
     * @var User
     */
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getWebhookChangeType(): string
    {
        return WebhookEvent::RESET_PASSWORD;
    }

    public function getEventName(): string
    {
        return 'user.reset_password';
    }

    public function getWebhookEntityClass(): string
    {
        return 'user';
    }

    /**
     * @return User
     */
    public function getWebhookEntity(): ?object
    {
        return $this->user;
    }

    public function getWebhookEntityBeforeEdit(): ?object
    {
        return null;
    }

    public function getWebhookEntityId(): ?int
    {
        return $this->user->getId();
    }
}
