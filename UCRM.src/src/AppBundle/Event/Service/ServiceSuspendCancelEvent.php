<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Service;

use AppBundle\Entity\Service;
use AppBundle\Entity\WebhookEvent;

final class ServiceSuspendCancelEvent extends AbstractServiceEvent
{
    /**
     * @var Service
     */
    protected $serviceBeforeUpdate;

    public function __construct(Service $service, Service $serviceBeforeUpdate)
    {
        parent::__construct($service);
        $this->serviceBeforeUpdate = $serviceBeforeUpdate;
    }

    public function getServiceBeforeUpdate(): Service
    {
        return $this->serviceBeforeUpdate;
    }

    /**
     * @return Service
     */
    public function getWebhookEntityBeforeEdit(): ?object
    {
        return $this->getServiceBeforeUpdate();
    }

    public function getWebhookChangeType(): string
    {
        return WebhookEvent::UNSUSPEND;
    }

    public function getEventName(): string
    {
        return 'service.suspend_cancel';
    }
}
