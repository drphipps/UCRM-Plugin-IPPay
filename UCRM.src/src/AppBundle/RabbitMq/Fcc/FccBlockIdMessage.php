<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Fcc;

use AppBundle\Entity\Service;
use Nette\Utils\Json;
use RabbitMqBundle\MessageInterface;

class FccBlockIdMessage implements MessageInterface
{
    /**
     * @var Service
     */
    private $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    public function getProducer(): string
    {
        return 'fcc_block_id';
    }

    public function getBody(): string
    {
        return Json::encode(
            [
                'serviceId' => $this->service->getId(),
            ]
        );
    }

    public function getBodyProperties(): array
    {
        return [
            'serviceId',
        ];
    }

    public function getRoutingKey(): string
    {
        return 'fcc_block_id';
    }

    public function getProperties(): array
    {
        return [];
    }
}
