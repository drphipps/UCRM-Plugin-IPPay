<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace RabbitMqBundle;

use OldSound\RabbitMqBundle\RabbitMq\BaseAmqp;

class QueueCleaner extends BaseAmqp
{
    public function purgeQueue(string $name): void
    {
        $this->getChannel()->queue_purge($name, true);
    }
}
