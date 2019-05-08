<?php
/*
 * @copyright Copyright (c) 2019 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Stripe\Webhook;

use AppBundle\Component\Stripe\StripeWebhookHandler;
use AppBundle\Entity\Client;
use AppBundle\Entity\PaymentPlan;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Event;

class CustomerDeletedEventHandler
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
            $event->type === StripeWebhookHandler::EVENT_CUSTOMER_DELETED,
            new \InvalidArgumentException(
                sprintf(
                    'Invalid event type "%s", only "%s" events can be handled here.',
                    $event->type,
                    StripeWebhookHandler::EVENT_CUSTOMER_DELETED
                )
            )
        );

        $customer = $event->data->object;

        $client = $this->entityManager->getRepository(Client::class)->findOneBy(
            [
                'stripeCustomerId' => $customer->id,
            ]
        );

        if (! $client) {
            return;
        }

        $this->entityManager->transactional(
            function () use ($client) {
                $client->setStripeCustomerId(null);

                foreach ($client->getActivePaymentPlans() as $paymentPlan) {
                    assert($paymentPlan instanceof PaymentPlan);

                    $paymentPlan->setActive(false);
                    $paymentPlan->setCanceledDate(new \DateTime());
                }
            }
        );
    }
}
