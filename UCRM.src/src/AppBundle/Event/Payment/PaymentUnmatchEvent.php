<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Payment;

use AppBundle\Entity\Client;
use AppBundle\Entity\Payment;
use AppBundle\Entity\WebhookEvent;

final class PaymentUnmatchEvent extends AbstractPaymentEvent
{
    public function __construct(Payment $payment, ?Client $client)
    {
        parent::__construct($payment);
        // $this->payment->getClient() may be already null (unmatched)
        $this->client = $client;
    }

    public function getWebhookChangeType(): string
    {
        return WebhookEvent::EDIT;
    }

    public function getEventName(): string
    {
        return 'payment.unmatch';
    }
}
