<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Invoice;

use Nette\Utils\Json;
use RabbitMqBundle\MessageInterface;

class InitializeDraftGenerationMessage implements MessageInterface
{
    /**
     * @var \DateTimeInterface
     */
    private $nextInvoicingDay;

    /**
     * @var bool
     */
    private $sendNotificationOnFinish;

    public function __construct(\DateTimeInterface $nextInvoicingDay, bool $sendNotificationOnFinish)
    {
        $this->nextInvoicingDay = $nextInvoicingDay;
        $this->sendNotificationOnFinish = $sendNotificationOnFinish;
    }

    /**
     * {@inheritdoc}
     */
    public function getProducer(): string
    {
        return 'initialize_draft_generation';
    }

    /**
     * {@inheritdoc}
     */
    public function getBody(): string
    {
        return Json::encode(
            [
                'nextInvoicingDay' => $this->nextInvoicingDay->format(\DateTime::ATOM),
                'sendNotificationOnFinish' => $this->sendNotificationOnFinish,
            ]
        );
    }

    public function getBodyProperties(): array
    {
        return [
            'nextInvoicingDay',
            'sendNotificationOnFinish',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutingKey(): string
    {
        return 'initialize_draft_generation';
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties(): array
    {
        return [];
    }
}
