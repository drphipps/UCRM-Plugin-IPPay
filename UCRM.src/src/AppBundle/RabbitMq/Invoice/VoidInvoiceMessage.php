<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Invoice;

use AppBundle\Entity\Financial\Invoice;
use Nette\Utils\Json;
use RabbitMqBundle\MessageInterface;

class VoidInvoiceMessage implements MessageInterface
{
    /**
     * @var Invoice
     */
    private $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * {@inheritdoc}
     */
    public function getProducer(): string
    {
        return 'void_invoices';
    }

    /**
     * {@inheritdoc}
     */
    public function getBody(): string
    {
        return Json::encode(
            [
                'invoiceId' => $this->invoice->getId(),
            ]
        );
    }

    public function getBodyProperties(): array
    {
        return [
            'invoiceId',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutingKey(): string
    {
        return 'void_invoices';
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties(): array
    {
        return [];
    }
}
