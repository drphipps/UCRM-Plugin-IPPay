<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Payment;

use AppBundle\Entity\Download;
use Nette\Utils\Json;
use RabbitMqBundle\MessageInterface;

class ExportPaymentReceiptMessage implements MessageInterface
{
    /**
     * @var Download
     */
    private $download;

    /**
     * @var array
     */
    private $ids;

    public function __construct(Download $download, array $ids)
    {
        $this->download = $download;
        $this->ids = $ids;
    }

    public function getProducer(): string
    {
        return 'export_payment_receipt';
    }

    public function getBody(): string
    {
        return Json::encode(
            [
                'download' => $this->download->getId(),
                'payments' => $this->ids,
            ]
        );
    }

    public function getBodyProperties(): array
    {
        return [
            'download',
            'payments',
        ];
    }

    public function getRoutingKey(): string
    {
        return 'export_payment_receipt';
    }

    public function getProperties(): array
    {
        return [];
    }
}
