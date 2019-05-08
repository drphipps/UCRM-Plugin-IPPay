<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Exception;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\Exception\StopConsumerException;

class RejectRequeueStopConsumerException extends StopConsumerException
{
    public function getHandleCode()
    {
        return ConsumerInterface::MSG_REJECT_REQUEUE;
    }
}
