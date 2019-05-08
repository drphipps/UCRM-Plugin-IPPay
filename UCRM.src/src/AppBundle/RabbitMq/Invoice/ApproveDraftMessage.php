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

class ApproveDraftMessage implements MessageInterface
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
        return 'approve_draft';
    }

    /**
     * {@inheritdoc}
     */
    public function getBody(): string
    {
        return Json::encode(
            [
                'invoice' => $this->invoice->getId(),
            ]
        );
    }

    public function getBodyProperties(): array
    {
        return [
            'invoice',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutingKey(): string
    {
        return 'approve_draft';
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties(): array
    {
        return [];
    }
}
