<?php
/*
 * @copyright Copyright (c) 2019 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Stripe\Webhook;

use AppBundle\Component\Stripe\StripeWebhookHandler;
use AppBundle\Entity\PaymentPlan;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Event;

class CustomerSubscriptionDeletedEventHandler
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function handle(Event $event): void
    {
        assert(
            $event->type === StripeWebhookHandler::EVENT_CUSTOMER_SUBSCRIPTION_DELETED,
            new \InvalidArgumentException(
                sprintf(
                    'Invalid event type "%s", only "%s" events can be handled here.',
                    $event->type,
                    StripeWebhookHandler::EVENT_CUSTOMER_SUBSCRIPTION_DELETED
                )
            )
        );

        $subscription = $event->data->object;

        $paymentPlan = $this->entityManager->getRepository(PaymentPlan::class)->findOneBy(
            [
                'providerSubscriptionId' => $subscription->id,
                'provider' => [PaymentPlan::PROVIDER_STRIPE, PaymentPlan::PROVIDER_STRIPE_ACH],
                'active' => true,
            ]
        );

        if (! $paymentPlan) {
            return;
        }

        $this->entityManager->transactional(
            function () use ($paymentPlan) {
                $paymentPlan->setStatus(PaymentPlan::STATUS_CANCELLED);
                $paymentPlan->setActive(false);
                $paymentPlan->setCanceledDate(new \DateTime());
            }
        );
    }
}
