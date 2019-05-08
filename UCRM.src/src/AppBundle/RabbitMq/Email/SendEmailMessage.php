<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Email;

use Nette\Utils\Json;
use RabbitMqBundle\MessageInterface;

class SendEmailMessage implements MessageInterface
{
    /**
     * @var string|null
     */
    private $eventClass;

    /**
     * @var array|null
     */
    private $eventData;

    /**
     * @var string
     */
    private $messageId;

    /**
     * @var int
     */
    private $priority;

    public function __construct(string $messageId, int $priority, ?string $eventClass = null, ?array $eventData = null)
    {
        $this->messageId = $messageId;
        $this->priority = $priority;
        $this->eventClass = $eventClass;
        $this->eventData = $eventData;
    }

    /**
     * {@inheritdoc}
     */
    public function getProducer(): string
    {
        return 'send_email';
    }

    /**
     * {@inheritdoc}
     */
    public function getBody(): string
    {
        return Json::encode(
            [
                'messageId' => $this->messageId,
                'eventClass' => $this->eventClass,
                'eventData' => $this->eventData,
            ]
        );
    }

    public function getBodyProperties(): array
    {
        return [
            'messageId',
            'eventClass',
            'eventData',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutingKey(): string
    {
        return 'send_email';
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties(): array
    {
        return [
            'priority' => $this->priority,
        ];
    }
}
