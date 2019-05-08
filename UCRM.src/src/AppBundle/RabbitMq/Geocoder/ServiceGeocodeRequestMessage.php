<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Geocoder;

use AppBundle\Entity\Service;
use Nette\Utils\Json;
use RabbitMqBundle\MessageInterface;

class ServiceGeocodeRequestMessage implements MessageInterface
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
        return 'geocode_service';
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
        return 'geocode_service';
    }

    public function getProperties(): array
    {
        return [];
    }
}
