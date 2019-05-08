<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Invoice;

use AppBundle\Entity\Client;
use Nette\Utils\Json;
use RabbitMqBundle\MessageInterface;

class GenerateDraftsMessage implements MessageInterface
{
    /**
     * @var string
     */
    private $draftGenerationUUID;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var \DateTimeInterface
     */
    private $nextInvoicingDay;

    public function __construct(string $draftGenerationUUID, Client $client, \DateTimeInterface $nextInvoicingDay)
    {
        $this->draftGenerationUUID = $draftGenerationUUID;
        $this->client = $client;
        $this->nextInvoicingDay = $nextInvoicingDay;
    }

    /**
     * {@inheritdoc}
     */
    public function getProducer(): string
    {
        return 'generate_drafts';
    }

    /**
     * {@inheritdoc}
     */
    public function getBody(): string
    {
        return Json::encode(
            [
                'draftGenerationUUID' => $this->draftGenerationUUID,
                'clientId' => $this->client->getId(),
                'nextInvoicingDay' => $this->nextInvoicingDay->format(\DateTime::ATOM),
            ]
        );
    }

    public function getBodyProperties(): array
    {
        return [
            'draftGenerationUUID',
            'clientId',
            'nextInvoicingDay',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutingKey(): string
    {
        return 'generate_drafts';
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties(): array
    {
        return [];
    }
}
