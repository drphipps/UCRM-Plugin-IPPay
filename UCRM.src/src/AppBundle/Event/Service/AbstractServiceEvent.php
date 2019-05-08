<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Service;

use AppBundle\Entity\Service;
use AppBundle\Interfaces\WebhookRequestableInterface;
use Symfony\Component\EventDispatcher\Event;

abstract class AbstractServiceEvent extends Event implements WebhookRequestableInterface
{
    /**
     * @var Service
     */
    protected $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    public function getService(): Service
    {
        return $this->service;
    }

    public function getWebhookEntityClass(): string
    {
        return 'service';
    }

    /**
     * @return Service
     */
    public function getWebhookEntity(): ?object
    {
        return $this->service;
    }

    /**
     * @return Service|null
     */
    public function getWebhookEntityBeforeEdit(): ?object
    {
        return null;
    }

    public function getWebhookEntityId(): ?int
    {
        return $this->getWebhookEntity()->getId();
    }
}
