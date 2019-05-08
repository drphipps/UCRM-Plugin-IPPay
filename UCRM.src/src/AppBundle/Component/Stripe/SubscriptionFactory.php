<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Stripe;

use AppBundle\Entity\ClientBankAccount;
use AppBundle\Entity\PaymentPlan;
use AppBundle\Facade\ClientBankAccountFacade;
use Doctrine\ORM\EntityManagerInterface;

class SubscriptionFactory
{
    /**
     * @var ClientBankAccountFacade
     */
    private $clientBankAccountFacade;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(ClientBankAccountFacade $clientBankAccountFacade, EntityManagerInterface $entityManager)
    {
        $this->clientBankAccountFacade = $clientBankAccountFacade;
        $this->entityManager = $entityManager;
    }

    public function create(
        PaymentPlan $paymentPlan,
        string $token,
        bool $sandbox,
        ?string $stripeEmail = null
    ): Subscription {
        $subscription = new Subscription(
            $this->entityManager,
            $sandbox,
            $paymentPlan,
            $token,
            $paymentPlan->getAmountInSmallestUnit(),
            $paymentPlan->getCurrency()->getCode(),
            $paymentPlan->getName(),
            $stripeEmail
        );

        return $subscription;
    }

    public function createAch(
        PaymentPlan $paymentPlan,
        ClientBankAccount $bankAccount,
        bool $sandbox
    ): SubscriptionAch {
        $subscription = new SubscriptionAch(
            $this->entityManager,
            $this->clientBankAccountFacade,
            $bankAccount,
            $sandbox,
            $paymentPlan,
            $paymentPlan->getAmountInSmallestUnit(),
            $paymentPlan->getCurrency()->getCode(),
            $paymentPlan->getName()
        );

        return $subscription;
    }
}
