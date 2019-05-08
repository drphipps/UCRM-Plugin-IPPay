<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Service;

use AppBundle\Entity\Service;
use AppBundle\Entity\WebhookEvent;

final class ServiceAddEvent extends AbstractServiceEvent
{
    /**
     * @var Service|null
     */
    private $supersededService;

    public function __construct(Service $service, ?Service $supersededService = null)
    {
        parent::__construct($service);
        $this->supersededService = $supersededService;
    }

    public function getSupersededService(): ?Service
    {
        return $this->supersededService;
    }

    /**
     * @return Service|null
     */
    public function getWebhookEntityBeforeEdit(): ?object
    {
        return $this->getSupersededService();
    }

    public function getWebhookChangeType(): string
    {
        return WebhookEvent::INSERT;
    }

    public function getEventName(): string
    {
        return 'service.add';
    }
}
