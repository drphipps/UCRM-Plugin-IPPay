<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Stripe;

use AppBundle\Component\Stripe\Exception\StripeException;
use AppBundle\Entity\ClientBankAccount;
use AppBundle\Entity\PaymentPlan;
use AppBundle\Facade\ClientBankAccountFacade;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Customer;
use Stripe\Error;
use Stripe\Plan;
use Stripe\Stripe;

class SubscriptionAch
{
    public const METADATA_PAYMENT_SOURCE = 'UCRM';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ClientBankAccountFacade
     */
    private $bankAccountFacade;

    /**
     * @var ClientBankAccount
     */
    private $clientBankAccount;

    /**
     * @var bool
     */
    private $sandbox;

    /**
     * @var PaymentPlan
     */
    private $paymentPlan;

    /**
     * @var int
     */
    private $amount;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var string
     */
    private $description;

    public function __construct(
        EntityManagerInterface $entityManager,
        ClientBankAccountFacade $bankAccountFacade,
        ClientBankAccount $clientBankAccount,
        bool $sandbox,
        PaymentPlan $paymentPlan,
        int $amount,
        string $currency,
        string $description
    ) {
        $this->entityManager = $entityManager;
        $this->bankAccountFacade = $bankAccountFacade;
        $this->clientBankAccount = $clientBankAccount;
        $this->sandbox = $sandbox;
        $this->paymentPlan = $paymentPlan;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->description = $description;
    }

    public function execute(): void
    {
        $client = $this->paymentPlan->getClient();
        $organization = $client->getOrganization();
        Stripe::setApiKey($organization->getStripeSecretKey($this->sandbox));

        if ($this->paymentPlan->getProviderPlanId()) {
            // Delete from Stripe if plan with same plan ID exists
            try {
                $existingPlan = Plan::retrieve($this->paymentPlan->getProviderPlanId());
                $existingPlan->delete();
            } catch (Error\InvalidRequest $e) {
                if ($e->getHttpStatus() !== 404) {
                    throw $e;
                }
            }
        }

        Plan::create(
            [
                'amount' => $this->amount,
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
                    'createdBy' => self::METADATA_PAYMENT_SOURCE,
                ],
            ]
        );

        $customer = null;

        try {
            $customer = Customer::retrieve($this->clientBankAccount->getStripeCustomerId());
            if ($customer->deleted) {
                $customer = null;
                $this->bankAccountFacade->clearStripeClient($this->clientBankAccount);
            }
        } catch (Error\InvalidRequest $e) {
            if ($e->getHttpStatus() !== 404) {
                throw $e;
            }
        }

        if (! $customer) {
            $this->clientBankAccount->setStripeBankAccountVerified(false);
            throw new StripeException('ACH subscription could not be created, because client has been deleted.');
        }

        $subscriptionOptions = [
            'customer' => $this->clientBankAccount->getStripeCustomerId(),
            'plan' => $this->paymentPlan->getProviderPlanId(),
        ];

        $startDate = $this->paymentPlan->getStartDate();
        if ($startDate && (new \DateTimeImmutable())->format('Y-m-d') !== $startDate->format('Y-m-d')) {
            $subscriptionOptions['trial_end'] = $startDate->getTimestamp();
        }

        $subscription = \Stripe\Subscription::create($subscriptionOptions);

        $this->paymentPlan->setProviderSubscriptionId($subscription->id);
        $this->paymentPlan->setActive(true);
        $this->entityManager->flush();
    }
}
