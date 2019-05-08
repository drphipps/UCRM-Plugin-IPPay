<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Geocoder;

use AppBundle\Entity\Client;
use Nette\Utils\Json;
use RabbitMqBundle\MessageInterface;

class ClientGeocodeRequestMessage implements MessageInterface
{
    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getProducer(): string
    {
        return 'geocode_client';
    }

    public function getBody(): string
    {
        return Json::encode(
            [
                'clientId' => $this->client->getId(),
            ]
        );
    }

    public function getBodyProperties(): array
    {
        return [
            'clientId',
        ];
    }

    public function getRoutingKey(): string
    {
        return 'geocode_client';
    }

    public function getProperties(): array
    {
        return [];
    }
}
