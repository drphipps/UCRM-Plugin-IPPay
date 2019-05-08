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

class UpdatePaymentPlanAmountMessage implements MessageInterface
{
    /**
     * @var PaymentPlan
     */
    private $paymentPlan;

    /**
     * @var int
     */
    private $newAmountInSmallestUnit;

    public function __construct(PaymentPlan $paymentPlan, int $newAmountInSmallestUnit)
    {
        $this->paymentPlan = $paymentPlan;
        $this->newAmountInSmallestUnit = $newAmountInSmallestUnit;
    }

    /**
     * {@inheritdoc}
     */
    public function getProducer(): string
    {
        return 'payment_plan_update_amount';
    }

    /**
     * {@inheritdoc}
     */
    public function getBody(): string
    {
        return Json::encode(
            [
                'paymentPlanId' => $this->paymentPlan->getId(),
                'newAmountInSmallestUnit' => $this->newAmountInSmallestUnit,
            ]
        );
    }

    public function getBodyProperties(): array
    {
        return [
            'paymentPlanId',
            'newAmountInSmallestUnit',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutingKey(): string
    {
        return 'payment_plan_update_amount';
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties(): array
    {
        return [];
    }
}
