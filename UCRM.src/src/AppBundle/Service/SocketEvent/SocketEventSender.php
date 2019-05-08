<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\SocketEvent;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;

class SocketEventSender
{
    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function send(SocketEvent $event, string $method = 'post'): void
    {
        try {
            $this->client->request(
                $method,
                $event->getEvent(),
                [
                    'json' => $event->getBody(),
                ]
            );
        } catch (TransferException $exception) {
            throw new SocketEventException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
