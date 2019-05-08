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

class ExportInvoicesMessage implements MessageInterface
{
    public const FORMAT_PDF = 'pdf';

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

    /**
     * {@inheritdoc}
     */
    public function getProducer(): string
    {
        return 'export_invoices';
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
            ]
        );
    }

    public function getBodyProperties(): array
    {
        return [
            'download',
            'invoices',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutingKey(): string
    {
        return 'export_invoices';
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties(): array
    {
        return [];
    }
}
