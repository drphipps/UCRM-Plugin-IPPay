<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\RabbitMq\Job;

use AppBundle\Entity\Download;
use Nette\Utils\Json;
use RabbitMqBundle\MessageInterface;

class ExportJobMessage implements MessageInterface
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

    public function getProducer(): string
    {
        return 'export_job';
    }

    public function getBody(): string
    {
        return Json::encode(
            [
                'download' => $this->download->getId(),
                'jobs' => $this->ids,
                'format' => $this->format,
            ]
        );
    }

    public function getBodyProperties(): array
    {
        return [
            'download',
            'jobs',
            'format',
        ];
    }

    public function getRoutingKey(): string
    {
        return 'export_job';
    }

    public function getProperties(): array
    {
        return [];
    }
}
