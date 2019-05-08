<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\ClientImport;

use Nette\Utils\Json;
use RabbitMqBundle\MessageInterface;

class ClientImportMessage implements MessageInterface
{
    /**
     * @var array
     */
    private $data;

    /**
     * @var int
     */
    private $organizationId;

    /**
     * @var string
     */
    private $csvImportUuid;

    public function __construct(array $data, int $organizationId, string $csvImportUuid)
    {
        $this->data = $data;
        $this->organizationId = $organizationId;
        $this->csvImportUuid = $csvImportUuid;
    }

    public function getProducer(): string
    {
        return 'client_import';
    }

    public function getBody(): string
    {
        return Json::encode(
            [
                'data' => $this->data,
                'organizationId' => $this->organizationId,
                'csvImportUuid' => $this->csvImportUuid,
            ]
        );
    }

    public function getBodyProperties(): array
    {
        return [
            'data',
            'organizationId',
            'csvImportUuid',
        ];
    }

    public function getRoutingKey(): string
    {
        return 'client_import';
    }

    public function getProperties(): array
    {
        return [];
    }
}
