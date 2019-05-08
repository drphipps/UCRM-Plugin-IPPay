<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Subscriber\Job;

use RabbitMqBundle\RabbitMqEnqueuer;

/**
 * @property RabbitMqEnqueuer $rabbitMqEnqueuer
 */
trait RabbitMessagesSubscriberTrait
{
    /**
     * @var array
     */
    private $rabbitMessages = [];

    public function preFlush(): void
    {
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
        foreach ($this->rabbitMessages as $message) {
            $this->rabbitMqEnqueuer->enqueue($message);
        }

        $this->rabbitMessages = [];
    }

    public function rollback(): void
    {
        $this->rabbitMessages = [];
    }
}
