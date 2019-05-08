<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace RabbitMqBundle;

use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RabbitMqEnqueuer
{
    /**
     * @var array
     */
    private $producers;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var bool
     */
    private $enabled = true;

    public function __construct(array $producers, ContainerInterface $container)
    {
        $this->producers = $producers;
        $this->container = $container;
    }

    public function enqueue(MessageInterface $message): void
    {
        if (! $this->enabled) {
            return;
        }

        $producerName = $message->getProducer();

        if (! isset($this->producers[$producerName])) {
            throw new \InvalidArgumentException();
        }

        /** @var ProducerInterface $producer */
        $producer = $this->container->get($this->producers[$producerName]);
        $producer->publish($message->getBody(), $message->getRoutingKey(), $message->getProperties());
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }
}
