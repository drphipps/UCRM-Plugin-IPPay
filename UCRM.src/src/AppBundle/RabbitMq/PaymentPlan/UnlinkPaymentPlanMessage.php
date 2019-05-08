<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\PaymentPlan;

use AppBundle\Entity\PaymentPlan;
use Nette\Utils\Json;
use RabbitMqBundle\MessageInterface;

class UnlinkPaymentPlanMessage implements MessageInterface
{
    /**
     * @var PaymentPlan
     */
    private $paymentPlan;

    public function __construct(PaymentPlan $paymentPlan)
    {
        $this->paymentPlan = $paymentPlan;
    }

    /**
     * {@inheritdoc}
     */
    public function getProducer(): string
    {
        return 'payment_plan_unlink';
    }

    /**
     * {@inheritdoc}
     */
    public function getBody(): string
    {
        return Json::encode(
            [
                'paymentPlanId' => $this->paymentPlan->getId(),
            ]
        );
    }

    public function getBodyProperties(): array
    {
        return [
            'paymentPlanId',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutingKey(): string
    {
        return 'payment_plan_unlink';
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties(): array
    {
        return [];
    }
}
