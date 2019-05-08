<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\PaymentPlan;

use AppBundle\Entity\PaymentPlan;
use AppBundle\Entity\WebhookEvent;

final class PaymentPlanEditEvent extends AbstractPaymentPlanEvent
{
    private $paymentPlanBeforeUpdate;

    public function __construct(PaymentPlan $paymentPlan, PaymentPlan $paymentPlanBeforeUpdate)
    {
        parent::__construct($paymentPlan);
        $this->paymentPlanBeforeUpdate = $paymentPlanBeforeUpdate;
    }

    public function getPaymentPlanBeforeUpdate(): PaymentPlan
    {
        return $this->paymentPlanBeforeUpdate;
    }

    /**
     * @return PaymentPlan
     */
    public function getWebhookEntityBeforeEdit(): ?object
    {
        return $this->getPaymentPlanBeforeUpdate();
    }

    public function getWebhookChangeType(): string
    {
        return WebhookEvent::EDIT;
    }

    public function getEventName(): string
    {
        return 'subscription.edit';
    }
}
