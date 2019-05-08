<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\CsvImport;

use AppBundle\Entity\Client;
use AppBundle\Entity\Service;
use AppBundle\Event\Client\ClientAddImportEvent;
use AppBundle\Event\Service\ServiceAddImportEvent;
use AppBundle\RabbitMq\Geocoder\ClientGeocodeRequestMessage;
use AppBundle\RabbitMq\Geocoder\ServiceGeocodeRequestMessage;
use Ds\Queue;
use RabbitMqBundle\RabbitMqEnqueuer;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class EntityImportSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    /**
     * @var Queue|Client[]
     */
    private $clientQueue;

    /**
     * @var Queue|Service[]
     */
    private $serviceQueue;

    public function __construct(RabbitMqEnqueuer $rabbitMqEnqueuer)
    {
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;
        $this->clientQueue = new Queue();
        $this->serviceQueue = new Queue();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ClientAddImportEvent::class => 'handleClientAddEvent',
            ServiceAddImportEvent::class => 'handleServiceAddEvent',
        ];
    }

    public function handleClientAddEvent(ClientAddImportEvent $event): void
    {
        $client = $event->getClient();
        if (! $client->hasAddressGps()) {
            $this->clientQueue->push($client);
        }
    }

    public function handleServiceAddEvent(ServiceAddImportEvent $event): void
    {
        $service = $event->getService();
        if (! $service->hasAddressGps()) {
            $this->serviceQueue->push($service);
        }
    }

    public function preFlush(): void
    {
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
        foreach ($this->serviceQueue as $service) {
            $this->rabbitMqEnqueuer->enqueue(new ServiceGeocodeRequestMessage($service));
        }
        foreach ($this->clientQueue as $client) {
            $this->rabbitMqEnqueuer->enqueue(new ClientGeocodeRequestMessage($client));
        }
    }

    public function rollback(): void
    {
        $this->clientQueue->clear();
        $this->serviceQueue->clear();
    }
}
