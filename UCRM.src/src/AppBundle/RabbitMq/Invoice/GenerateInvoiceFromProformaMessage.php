<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Invoice;

use Nette\Utils\Json;
use RabbitMqBundle\MessageInterface;

class GenerateInvoiceFromProformaMessage implements MessageInterface
{
    /**
     * @var int
     */
    private $proformaInvoiceId;

    public function __construct(int $proformaInvoiceId)
    {
        $this->proformaInvoiceId = $proformaInvoiceId;
    }

    public function getProducer(): string
    {
        return 'generate_invoices_from_proformas';
    }

    public function getBody(): string
    {
        return Json::encode(
            [
                'proformaInvoiceId' => $this->proformaInvoiceId,
            ]
        );
    }

    public function getBodyProperties(): array
    {
        return [
            'proformaInvoiceId',
        ];
    }

    public function getRoutingKey(): string
    {
        return 'generate_invoices_from_proformas';
    }

    public function getProperties(): array
    {
        return [];
    }
}
