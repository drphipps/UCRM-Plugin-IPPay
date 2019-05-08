<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\ClientDelete;

use Nette\Utils\Json;
use RabbitMqBundle\MessageInterface;

class ClientDeleteMessage implements MessageInterface
{
    public const OPERATION_ARCHIVE = 'OPERATION_ARCHIVE';
    public const OPERATION_DELETE = 'OPERATION_DELETE';

    private const OPERATIONS = [
        self::OPERATION_ARCHIVE,
        self::OPERATION_DELETE,
    ];

    /**
     * @var int
     */
    private $clientId;

    /**
     * @var string
     */
    private $operation;

    public function __construct(int $clientId, string $operation)
    {
        if (! in_array($operation, self::OPERATIONS, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    '"%s" operation not supported. Possible operations: %s',
                    $operation,
                    implode(', ', self::OPERATIONS)
                )
            );
        }

        $this->clientId = $clientId;
        $this->operation = $operation;
    }

    public function getProducer(): string
    {
        return 'delete_clients';
    }

    public function getBody(): string
    {
        return Json::encode(
            [
                'clientId' => $this->clientId,
                'operation' => $this->operation,
            ]
        );
    }

    public function getBodyProperties(): array
    {
        return [
            'clientId',
            'operation',
        ];
    }

    public function getRoutingKey(): string
    {
        return 'delete_clients';
    }

    public function getProperties(): array
    {
        return [];
    }
}
