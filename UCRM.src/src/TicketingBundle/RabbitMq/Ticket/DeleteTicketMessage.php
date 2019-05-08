<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\RabbitMq\Ticket;

use Nette\Utils\Json;
use RabbitMqBundle\MessageInterface;

class DeleteTicketMessage implements MessageInterface
{
    /**
     * @var int
     */
    private $ticketId;

    public function __construct(int $ticketId)
    {
        $this->ticketId = $ticketId;
    }

    public function getProducer(): string
    {
        return 'delete_tickets';
    }

    public function getBody(): string
    {
        return Json::encode(
            [
                'ticketId' => $this->ticketId,
            ]
        );
    }

    public function getBodyProperties(): array
    {
        return [
            'ticketId',
        ];
    }

    public function getRoutingKey(): string
    {
        return 'delete_tickets';
    }

    public function getProperties(): array
    {
        return [];
    }
}
