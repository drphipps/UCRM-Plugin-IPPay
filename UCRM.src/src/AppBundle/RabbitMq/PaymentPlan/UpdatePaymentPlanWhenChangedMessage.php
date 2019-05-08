<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\PaymentPlan;

use Nette\Utils\Json;
use RabbitMqBundle\MessageInterface;

class UpdatePaymentPlanWhenChangedMessage implements MessageInterface
{
    /**
     * @var int
     */
    private $serviceId;

    public function __construct(int $serviceId)
    {
        $this->serviceId = $serviceId;
    }

    /**
     * {@inheritdoc}
     */
    public function getProducer(): string
    {
        return 'payment_plan_update_when_changed';
    }

    /**
     * {@inheritdoc}
     */
    public function getBody(): string
    {
        return Json::encode(
            [
                'serviceId' => $this->serviceId,
            ]
        );
    }

    public function getBodyProperties(): array
    {
        return [
            'serviceId',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutingKey(): string
    {
        return 'payment_plan_update_when_changed';
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties(): array
    {
        return [];
    }
}
