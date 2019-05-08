<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Invoice;

use AppBundle\Entity\Download;
use Nette\Utils\Json;
use RabbitMqBundle\MessageInterface;

class ExportInvoiceOverviewMessage implements MessageInterface
{
    public const FORMAT_CSV = 'csv';
    public const FORMAT_PDF = 'pdf';

    /**
     * @var Download
     */
    private $download;

    /**
     * @var array
     */
    private $ids;

    /**
     * @var string
     */
    private $format;

    public function __construct(Download $download, array $ids, string $format)
    {
        $this->download = $download;
        $this->ids = $ids;
        $this->format = $format;
    }

    /**
     * {@inheritdoc}
     */
    public function getProducer(): string
    {
        return 'export_invoice_overview';
    }

    /**
     * {@inheritdoc}
     */
    public function getBody(): string
    {
        return Json::encode(
            [
                'download' => $this->download->getId(),
                'invoices' => $this->ids,
                'format' => $this->format,
            ]
        );
    }

    public function getBodyProperties(): array
    {
        return [
            'download',
            'invoices',
            'format',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutingKey(): string
    {
        return 'export_invoice_overview';
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties(): array
    {
        return [];
    }
}
