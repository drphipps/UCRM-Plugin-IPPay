<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Payment;

use AppBundle\Entity\Payment;
use AppBundle\Entity\WebhookEvent;

final class PaymentEditEvent extends AbstractPaymentEvent
{
    /**
     * @var Payment
     */
    private $paymentBeforeUpdate;

    public function __construct(Payment $payment, Payment $paymentBeforeUpdate)
    {
        parent::__construct($payment);
        $this->paymentBeforeUpdate = $paymentBeforeUpdate;
    }

    public function getPaymentBeforeUpdate(): Payment
    {
        return $this->paymentBeforeUpdate;
    }

    public function getWebhookChangeType(): string
    {
        return WebhookEvent::EDIT;
    }

    /**
     * @return Payment
     */
    public function getWebhookEntityBeforeEdit(): ?object
    {
        return $this->getPaymentBeforeUpdate();
    }

    public function getEventName(): string
    {
        return 'payment.edit';
    }
}
