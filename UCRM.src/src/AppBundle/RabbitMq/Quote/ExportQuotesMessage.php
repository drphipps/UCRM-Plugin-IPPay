<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\RabbitMq\Quote;

use AppBundle\Entity\Download;
use Nette\Utils\Json;
use RabbitMqBundle\MessageInterface;

class ExportQuotesMessage implements MessageInterface
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
        return 'export_quotes';
    }

    /**
     * {@inheritdoc}
     */
    public function getBody(): string
    {
        return Json::encode(
            [
                'download' => $this->download->getId(),
                'quotes' => $this->ids,
            ]
        );
    }

    public function getBodyProperties(): array
    {
        return [
            'download',
            'quotes',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutingKey(): string
    {
        return 'export_quotes';
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties(): array
    {
        return [];
    }
}
