<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\PaymentImport;

use AppBundle\Entity\Payment;
use Nette\Utils\Json;
use RabbitMqBundle\MessageInterface;

class PaymentImportMessage implements MessageInterface
{
    /**
     * @var Payment
     */
    private $payment;

    /**
     * @var string
     */
    private $csvImportUuid;

    public function __construct(Payment $payment, string $csvImportUuid)
    {
        $this->payment = $payment;
        $this->csvImportUuid = $csvImportUuid;
    }

    public function getProducer(): string
    {
        return 'client_import';
    }

    public function getBody(): string
    {
        return Json::encode(
            [
                'payment' => [
                    'client' => $this->payment->getClient()
                        ? $this->payment->getClient()->getId()
                        : null,
                    'method' => $this->payment->getMethod(),
                    'createdDate' => $this->payment->getCreatedDate()
                        ? $this->payment->getCreatedDate()->format(\DateTime::ATOM)
                        : null,
                    'amount' => $this->payment->getAmount(),
                    'currency' => $this->payment->getCurrency()
                        ? $this->payment->getCurrency()->getId()
                        : null,
                    'note' => $this->payment->getNote(),
                ],
                'csvImportUuid' => $this->csvImportUuid,
            ]
        );
    }

    public function getBodyProperties(): array
    {
        return [
            'payment' => [
                'client',
                'method',
                'createdDate',
                'amount',
                'currency',
                'note',
            ],
            'csvImportUuid',
        ];
    }

    public function getRoutingKey(): string
    {
        return 'payment_import';
    }

    public function getProperties(): array
    {
        return [];
    }
}
