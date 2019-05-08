<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Payment;

use Nette\Utils\Json;
use RabbitMqBundle\MessageInterface;

class GeneratePaymentReceiptNumberMessage implements MessageInterface
{
    /**
     * @var array
     */
    private $ids;

    public function __construct(array $ids)
    {
        $this->ids = $ids;
    }

    public function getProducer(): string
    {
        return 'generate_payment_receipt_numbers';
    }

    public function getBody(): string
    {
        return Json::encode(
            [
                'payments' => $this->ids,
            ]
        );
    }

    public function getBodyProperties(): array
    {
        return [
            'payments',
        ];
    }

    public function getRoutingKey(): string
    {
        return 'generate_payment_receipt_numbers';
    }

    public function getProperties(): array
    {
        return [];
    }
}
