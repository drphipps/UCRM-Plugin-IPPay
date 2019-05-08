<?php
/*
 * @copyright Copyright (c) 2019 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Stripe\Data;

use AppBundle\Entity\Client;
use AppBundle\Entity\PaymentPlan;
use AppBundle\Entity\PaymentStripePending;

class MatchingPayment
{
    /**
     * @var Client|null
     */
    public $client;

    /**
     * @var PaymentPlan|null
     */
    public $paymentPlan;

    /**
     * @var PaymentStripePending|null
     */
    public $paymentStripePending;

    public static function fromPaymentPlan(PaymentPlan $paymentPlan): self
    {
        $matchingPayment = new self();
        $matchingPayment->paymentPlan = $paymentPlan;
        $matchingPayment->client = $paymentPlan->getClient();

        return $matchingPayment;
    }

    public static function fromClient(Client $client): self
    {
        $matchingPayment = new self();
        $matchingPayment->client = $client;

        return $matchingPayment;
    }

    /**
     * $pendingPayment can be null and the payment can still belong to UCRM,
     * because in this case we check it via metadata.
     */
    public static function fromPendingStripePayment(?PaymentStripePending $pendingPayment): self
    {
        $matchingPayment = new self();
        $matchingPayment->paymentStripePending = $pendingPayment;
        $matchingPayment->client = $pendingPayment ? $pendingPayment->getClientBankAccount()->getClient() : null;

        return $matchingPayment;
    }

    public static function unattached(): self
    {
        return new self();
    }
}
