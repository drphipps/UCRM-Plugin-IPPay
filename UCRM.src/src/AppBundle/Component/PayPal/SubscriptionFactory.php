<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\PayPal;

use AppBundle\Entity\PaymentPlan;
use Doctrine\ORM\EntityManager;

class SubscriptionFactory
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var ApiContextFactory
     */
    private $apiContextFactory;

    public function __construct(EntityManager $em, ApiContextFactory $apiContextFactory)
    {
        $this->em = $em;
        $this->apiContextFactory = $apiContextFactory;
    }

    public function create(PaymentPlan $paymentPlan, string $returnUrl, string $cancelUrl, bool $sandbox): Subscription
    {
        $subscription = new Subscription($this->em, $this->apiContextFactory);
        $subscription->setSandbox($sandbox);
        $subscription->setReturnUrl($returnUrl);
        $subscription->setCancelUrl($cancelUrl);
        $subscription->setPaymentPlan($paymentPlan);
        $subscription->setAmount($paymentPlan->getAmountInSmallestUnit());
        $subscription->setCurrency($paymentPlan->getCurrency()->getCode());
        $subscription->setDescription($paymentPlan->getName());

        return $subscription;
    }
}
