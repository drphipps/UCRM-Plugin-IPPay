<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Import;

use Nette\Utils\Json;
use RabbitMqBundle\MessageInterface;

class SaveClientImportItemMessage implements MessageInterface
{
    /**
     * @var string
     */
    private $itemId;

    public function __construct(string $itemId)
    {
        $this->itemId = $itemId;
    }

    public function getProducer(): string
    {
        return 'save_client_import_item';
    }

    public function getBody(): string
    {
        return Json::encode(
            [
                'itemId' => $this->itemId,
            ]
        );
    }

    public function getBodyProperties(): array
    {
        return [
            'itemId',
        ];
    }

    public function getRoutingKey(): string
    {
        return 'save_client_import_item';
    }

    public function getProperties(): array
    {
        return [];
    }
}
