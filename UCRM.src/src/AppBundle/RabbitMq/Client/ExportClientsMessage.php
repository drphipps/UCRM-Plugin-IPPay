<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Client;

use AppBundle\Entity\Download;
use Nette\Utils\Json;
use RabbitMqBundle\MessageInterface;

class ExportClientsMessage implements MessageInterface
{
    public const FORMAT_CSV = 'csv';

    /**
     * @var Download
     */
    private $download;

    /**
     * @var string
     */
    private $format;

    /**
     * @var array
     */
    private $ids;

    public function __construct(Download $download, array $ids, string $format)
    {
        $this->download = $download;
        $this->format = $format;
        $this->ids = $ids;
    }

    public function getProducer(): string
    {
        return 'export_clients';
    }

    public function getBody(): string
    {
        return Json::encode(
            [
                'download' => $this->download->getId(),
                'format' => $this->format,
                'ids' => $this->ids,
            ]
        );
    }

    public function getBodyProperties(): array
    {
        return [
            'download',
            'format',
            'ids',
        ];
    }

    public function getRoutingKey(): string
    {
        return 'export_clients';
    }

    public function getProperties(): array
    {
        return [];
    }
}
