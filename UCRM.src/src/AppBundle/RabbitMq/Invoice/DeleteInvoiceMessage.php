<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Invoice;

use Nette\Utils\Json;
use RabbitMqBundle\MessageInterface;

class DeleteInvoiceMessage implements MessageInterface
{
    /**
     * @var int
     */
    private $invoiceId;

    public function __construct(int $invoiceId)
    {
        $this->invoiceId = $invoiceId;
    }

    public function getProducer(): string
    {
        return 'delete_invoices';
    }

    public function getBody(): string
    {
        return Json::encode(
            [
                'invoiceId' => $this->invoiceId,
            ]
        );
    }

    public function getBodyProperties(): array
    {
        return [
            'invoiceId',
        ];
    }

    public function getRoutingKey(): string
    {
        return 'delete_invoices';
    }

    public function getProperties(): array
    {
        return [];
    }
}
