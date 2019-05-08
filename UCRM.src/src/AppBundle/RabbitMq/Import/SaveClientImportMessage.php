<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Import;

use Nette\Utils\Json;
use RabbitMqBundle\MessageInterface;

class SaveClientImportMessage implements MessageInterface
{
    /**
     * @var string
     */
    private $importId;

    public function __construct(string $importId)
    {
        $this->importId = $importId;
    }

    public function getProducer(): string
    {
        return 'save_client_import';
    }

    public function getBody(): string
    {
        return Json::encode(
            [
                'importId' => $this->importId,
            ]
        );
    }

    public function getBodyProperties(): array
    {
        return [
            'importId',
        ];
    }

    public function getRoutingKey(): string
    {
        return 'save_client_import';
    }

    public function getProperties(): array
    {
        return [];
    }
}
