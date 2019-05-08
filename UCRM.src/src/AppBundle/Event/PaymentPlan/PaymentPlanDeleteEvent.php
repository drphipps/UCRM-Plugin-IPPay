<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\PaymentPlan;

use AppBundle\Entity\PaymentPlan;
use AppBundle\Entity\WebhookEvent;

final class PaymentPlanDeleteEvent extends AbstractPaymentPlanEvent
{
    /**
     * @var int|null
     */
    private $id;

    public function __construct(PaymentPlan $paymentPlan, ?int $id)
    {
        parent::__construct($paymentPlan);
        $this->id = $id;
    }

    public function getWebhookEntityId(): ?int
    {
        return $this->id;
    }

    public function getWebhookChangeType(): string
    {
        return WebhookEvent::DELETE;
    }

    public function getEventName(): string
    {
        return 'subscription.delete';
    }
}
