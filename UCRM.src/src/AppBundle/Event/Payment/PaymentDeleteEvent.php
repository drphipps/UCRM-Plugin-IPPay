<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Payment;

use AppBundle\Entity\Payment;
use AppBundle\Entity\WebhookEvent;

final class PaymentDeleteEvent extends AbstractPaymentEvent
{
    /**
     * @var int|null
     */
    private $id;

    public function __construct(Payment $payment, ?int $id)
    {
        parent::__construct($payment);
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
        return 'payment.delete';
    }
}
