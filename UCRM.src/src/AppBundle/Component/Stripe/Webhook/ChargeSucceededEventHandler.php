<?php
/*
 * @copyright Copyright (c) 2019 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Stripe\Webhook;

use AppBundle\Component\Stripe\ChargeAch;
use AppBundle\Component\Stripe\Data\MatchingPayment;
use AppBundle\Component\Stripe\Exception\StripePaymentIgnoredException;
use AppBundle\Component\Stripe\StripeWebhookHandler;
use AppBundle\Component\Stripe\SubscriptionAch;
use AppBundle\Entity\Client;
use AppBundle\Entity\Currency;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Payment;
use AppBundle\Entity\PaymentPlan;
use AppBundle\Entity\PaymentStripe;
use AppBundle\Entity\PaymentStripePending;
use AppBundle\Facade\PaymentFacade;
use AppBundle\Facade\PaymentStripePendingFacade;
use Doctrine\ORM\EntityManager;
use Ds\Set;
use Nette\Utils\Strings;
use Stripe\Charge;
use Stripe\Error\InvalidRequest;
use Stripe\Event;
use Stripe\Invoice;

class ChargeSucceededEventHandler
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var PaymentFacade
     */
    private $paymentFacade;

    /**
     * @var PaymentStripePendingFacade
     */
    private $paymentStripePendingFacade;

    public function __construct(
        EntityManager $entityManager,
        PaymentFacade $paymentFacade,
        PaymentStripePendingFacade $paymentStripePendingFacade
    ) {
        $this->entityManager = $entityManager;
        $this->paymentFacade = $paymentFacade;
        $this->paymentStripePendingFacade = $paymentStripePendingFacade;
    }

    /**
     * @throws StripePaymentIgnoredException
     */
    public function handle(Event $event, Organization $organization): void
    {
        assert(
            $event->type === StripeWebhookHandler::EVENT_CHARGE_SUCCEEDED,
            new \InvalidArgumentException(
                sprintf(
                    'Invalid event type "%s", only "%s" events can be handled here.',
                    $event->type,
                    StripeWebhookHandler::EVENT_CHARGE_SUCCEEDED
                )
            )
        );

        $charge = $event->data->object;
        assert($charge instanceof Charge);

        $this->ensurePaymentIsNotAlreadyProcessed($charge->id);
        $matchingPayment = $this->getMatchingPayment($charge, $organization);

        $currency = $this->entityManager->getRepository(Currency::class)->findOneBy(
            [
                'code' => Strings::upper($charge->currency),
            ]
        );

        $paymentStripe = new PaymentStripe();
        $paymentStripe->setOrganization($organization);
        $paymentStripe->setStripeId($charge->id);
        $paymentStripe->setBalanceTransaction($charge->balance_transaction);
        $paymentStripe->setCustomer($charge->customer);
        $paymentStripe->setAmount($charge->amount);
        $paymentStripe->setSourceCardId($charge->source->id);
        $paymentStripe->setSourceName($charge->source->name ?? null);
        $paymentStripe->setSourceFingerprint($charge->source->fingerprint ?? null);
        $paymentStripe->setStatus($charge->status);
        $paymentStripe->setClient($matchingPayment->client);

        $paymentPlan = $matchingPayment->paymentPlan;
        if ($paymentPlan) {
            $paymentPlan->setStatus(PaymentPlan::STATUS_ACTIVE);
            $this->entityManager->flush($paymentPlan);
        }

        $invoices = [];
        if ($matchingPayment->client && $currency) {
            $invoices = $this->entityManager->getRepository(\AppBundle\Entity\Financial\Invoice::class)
                ->getClientUnpaidInvoicesWithCurrency($matchingPayment->client, $currency);
        }

        $payment = new Payment();
        $payment->setCreatedDate(new \DateTime());
        $payment->setAmount($paymentStripe->getAmount() / (10 ** $currency->getFractionDigits()));
        $payment->setCurrency($currency);
        $payment->setClient($matchingPayment->client);

        // is Stripe ACH pending payment ?
        if ($matchingPayment->paymentStripePending) {
            $payment->setMethod(Payment::METHOD_STRIPE_ACH);
            $this->paymentFacade->handleCreateOnlinePayment(
                $payment,
                $paymentStripe,
                $matchingPayment->paymentStripePending->getPaymentToken()
            );
            $this->paymentStripePendingFacade->handleDelete($matchingPayment->paymentStripePending);

            return;
        }

        if ($paymentPlan) {
            $method = $charge->source->object === 'bank_account'
                ? Payment::METHOD_STRIPE_SUBSCRIPTION_ACH
                : Payment::METHOD_STRIPE_SUBSCRIPTION;
        } else {
            $method = $charge->source->object === 'bank_account'
                ? Payment::METHOD_STRIPE_ACH
                : Payment::METHOD_STRIPE;
        }

        $payment->setMethod($method);
        $this->paymentFacade->handleCreate($payment, $invoices, $paymentStripe);
    }

    private function ensurePaymentIsNotAlreadyProcessed(string $chargeId): void
    {
        $paymentStripe = $this->entityManager->getRepository(PaymentStripe::class)->findOneBy(
            [
                'stripeId' => $chargeId,
            ]
        );
        if ($paymentStripe) {
            // payment already processed
            throw new StripePaymentIgnoredException(
                sprintf('Payment with ID "%s" is already processed, ignoring.', $chargeId)
            );
        }
    }

    /**
     * @throws StripePaymentIgnoredException
     */
    private function getMatchingPayment(Charge $charge, Organization $organization): MatchingPayment
    {
        $matchTrace = new Set();

        // basic match by customer ID
        if ($charge->customer) {
            if ($client = $this->getClientByCustomerId($charge->customer)) {
                // check if it's subscription payment
                $paymentPlan = $this->getPaymentPlanFromCharge($charge);

                return $paymentPlan
                    ? MatchingPayment::fromPaymentPlan($paymentPlan)
                    : MatchingPayment::fromClient($client);
            }

            $matchTrace->add(
                sprintf('Client with Stripe Customer ID of "%s" not found in database.', $charge->customer)
            );
        }

        // match by subscription, both regular and ACH
        if ($matchingPayment = $this->matchFromInvoice($charge, $matchTrace)) {
            return $matchingPayment;
        }

        // match ACH one-time payment
        $createdBy = $charge->metadata->createdBy ?? null;
        if ($createdBy) {
            if ($createdBy === ChargeAch::METADATA_PAYMENT_SOURCE) {
                return MatchingPayment::fromPendingStripePayment($this->getPendingStripePayment($charge->id));
            }

            $matchTrace->add(
                sprintf(
                    'Stripe Charge metadata field "createdBy" (value: "%s") does not correspond to UCRM value.',
                    $createdBy
                )
            );
        }

        if ($organization->isStripeImportUnattachedPayments()) {
            return MatchingPayment::unattached();
        }

        throw new StripePaymentIgnoredException(
            sprintf(
                'Payment with Stripe Charge ID "%s" does not belong to UCRM, ignoring.%s%s',
                $charge->id,
                PHP_EOL . PHP_EOL,
                $matchTrace->join(PHP_EOL)
            )
        );
    }

    private function matchFromInvoice(Charge $charge, Set $matchTrace): ?MatchingPayment
    {
        if (! $charge->invoice) {
            return null;
        }

        try {
            $invoice = Invoice::retrieve($charge->invoice);
        } catch (InvalidRequest $exception) {
            $matchTrace->add($exception->getMessage());

            return null;
        }

        if ($invoice->subscription) {
            $paymentPlan = $this->getPaymentPlanBySubscriptionId($invoice->subscription);
            if ($paymentPlan) {
                return MatchingPayment::fromPaymentPlan($paymentPlan);
            }

            $matchTrace->add(
                sprintf('Subscription with ID "%s" not found in UCRM.', $invoice->subscription)
            );
        }

        // Stripe ACH
        foreach ($invoice->lines->data as $data) {
            $createdBy = $data->plan->metadata->createdBy ?? null;
            if ($createdBy === SubscriptionAch::METADATA_PAYMENT_SOURCE) {
                $pendingPayment = $this->entityManager->getRepository(PaymentStripePending::class)->findOneBy(
                    [
                        'paymentDetailsId' => $charge->id,
                    ]
                );

                return MatchingPayment::fromPendingStripePayment($pendingPayment);
            }

            if ($createdBy) {
                $matchTrace->add(
                    sprintf('Metadata field "createdBy" (value: "%s") does not correspond to UCRM value.', $createdBy)
                );
            }
        }

        return null;
    }

    private function getPaymentPlanFromCharge(Charge $charge): ?PaymentPlan
    {
        if (! $charge->invoice) {
            return null;
        }

        try {
            $invoice = Invoice::retrieve($charge->invoice);
        } catch (InvalidRequest $exception) {
            return null;
        }

        return $invoice->subscription
            ? $this->getPaymentPlanBySubscriptionId($invoice->subscription)
            : null;
    }

    private function getClientByCustomerId(string $customerId): ?Client
    {
        return $this->entityManager->getRepository(Client::class)->findOneBy(
            [
                'stripeCustomerId' => $customerId,
            ]
        );
    }

    private function getPaymentPlanBySubscriptionId(string $subscriptionId): ?PaymentPlan
    {
        return $this->entityManager->getRepository(PaymentPlan::class)->findOneBy(
            [
                'providerSubscriptionId' => $subscriptionId,
                'provider' => [PaymentPlan::PROVIDER_STRIPE, PaymentPlan::PROVIDER_STRIPE_ACH],
                'active' => true,
            ]
        );
    }

    private function getPendingStripePayment(string $chargeId): ?PaymentStripePending
    {
        return $this->entityManager->getRepository(PaymentStripePending::class)->findOneBy(
            [
                'paymentDetailsId' => $chargeId,
            ]
        );
    }
}
