<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Webhook;

use AppBundle\Facade\WebhookEventFacade;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\Service\Options;
use AppBundle\Service\Webhook\Requester;
use AppBundle\Util\Helpers;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class WebhookEventRequestConsumer extends AbstractConsumer
{
    /**
     * @var Requester
     */
    private $requester;

    /**
     * @var WebhookEventFacade
     */
    private $webhookEventFacade;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        Options $options,
        Requester $requester,
        WebhookEventFacade $webhookEventFacade
    ) {
        parent::__construct($entityManager, $logger, $options);

        $this->webhookEventFacade = $webhookEventFacade;
        $this->requester = $requester;
    }

    protected function getMessageClass(): string
    {
        return WebhookEventRequestMessage::class;
    }

    public function executeBody(array $data): int
    {
        if (Helpers::isDemo()) {
            return self::MSG_REJECT;
        }

        $webhookEvent = $this->webhookEventFacade->handleCreateFromData($data);
        $this->requester->send($webhookEvent);

        return self::MSG_ACK;
    }
}
