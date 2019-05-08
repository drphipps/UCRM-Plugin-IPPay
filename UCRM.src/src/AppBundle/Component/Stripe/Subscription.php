<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Stripe;

use AppBundle\Component\Stripe\Exception\StripeException;
use AppBundle\Entity\PaymentPlan;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Customer;
use Stripe\Error;
use Stripe\Plan;
use Stripe\Source;
use Stripe\Stripe;

class Subscription
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var bool
     */
    private $sandbox;

    /**
     * @var PaymentPlan
     */
    private $paymentPlan;

    /**
     * @var string
     */
    private $token;

    /**
     * @var int
     */
    private $amountInSmallestUnit;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string|null
     */
    private $stripeEmail;

    public function __construct(
        EntityManagerInterface $entityManager,
        bool $sandbox,
        PaymentPlan $paymentPlan,
        string $token,
        int $amountInSmallestUnit,
        string $currency,
        string $description,
        ?string $stripeEmail
    ) {
        $this->entityManager = $entityManager;
        $this->sandbox = $sandbox;
        $this->paymentPlan = $paymentPlan;
        $this->token = $token;
        $this->amountInSmallestUnit = $amountInSmallestUnit;
        $this->currency = $currency;
        $this->description = $description;
        $this->stripeEmail = $stripeEmail;
    }

    public function execute(): void
    {
        $client = $this->paymentPlan->getClient();
        $organization = $client->getOrganization();
        Stripe::setApiKey($organization->getStripeSecretKey($this->sandbox));

        $existingPlan = null;
        try {
            $existingPlan = Plan::retrieve($this->paymentPlan->getProviderPlanId());
            $existingPlan->delete();
        } catch (Error\InvalidRequest $e) {
            if ($e->getHttpStatus() !== 404) {
                throw $e;
            }
        }

        Plan::create(
            [
                'amount' => $this->amountInSmallestUnit,
                'interval' => 'month',
                'interval_count' => $this->paymentPlan->getPeriod(),
                'product' => [
                    'name' => $this->description,
                ],
                'currency' => $this->currency,
                'id' => $this->paymentPlan->getProviderPlanId(),
                'metadata' => [
                    'paymentPlanId' => $this->paymentPlan->getId(),
                    'clientId' => $client->getId(),
                ],
            ]
        );

        $customer = null;
        if ($client->getStripeCustomerId()) {
            try {
                $customer = Customer::retrieve($client->getStripeCustomerId());
                if ($customer->deleted) {
                    $customer = null;
                }
            } catch (Error\InvalidRequest $e) {
                if ($e->getHttpStatus() !== 404) {
                    throw $e;
                }
            }
        }

        if (! $customer) {
            $billingEmail = $client->getFirstBillingEmail();
            if (! $billingEmail && ! $this->stripeEmail) {
                throw new StripeException('Subscription could not be created, because client has no email set.');
            }

            $customer = Customer::create(
                [
                    'email' => $billingEmail ?: $this->stripeEmail,
                ]
            );

            $client->setStripeCustomerId($customer->id);
        }
        $source = Source::create(
            [
                'type' => 'card',
                'token' => $this->token,
            ]
        );
        $customer->sources->create(
            [
                'source' => $source->id,
            ]
        );
        Customer::update(
            $customer->id,
            [
                'default_source' => $source->id,
            ]
        );

        $subscriptionOptions = [
            'customer' => $client->getStripeCustomerId(),
            'plan' => $this->paymentPlan->getProviderPlanId(),
        ];

        $startDate = $this->paymentPlan->getStartDate();
        if ($startDate && (new \DateTime())->format('Y-m-d') !== $startDate->format('Y-m-d')) {
            $trialEnd = clone $startDate;
            $trialEnd->setTimezone(new \DateTimeZone('UTC'));
            $subscriptionOptions['trial_end'] = $trialEnd->getTimestamp();
        }

        $subscription = \Stripe\Subscription::create($subscriptionOptions);

        $this->paymentPlan->setProviderSubscriptionId($subscription->id);
        $this->paymentPlan->setActive(true);
        $this->entityManager->flush();
    }
}
