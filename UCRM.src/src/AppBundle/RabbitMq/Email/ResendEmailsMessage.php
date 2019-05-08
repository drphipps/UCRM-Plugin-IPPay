<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Email;

use Nette\Utils\Json;
use RabbitMqBundle\MessageInterface;

class ResendEmailsMessage implements MessageInterface
{
    /**
     * @var array
     */
    private $emailLogIds;

    public function __construct(array $emailLogIds)
    {
        $this->emailLogIds = $emailLogIds;
    }

    /**
     * {@inheritdoc}
     */
    public function getProducer(): string
    {
        return 'resend_emails';
    }

    /**
     * {@inheritdoc}
     */
    public function getBody(): string
    {
        return Json::encode(
            [
                'emailLogIds' => $this->emailLogIds,
            ]
        );
    }

    public function getBodyProperties(): array
    {
        return [
            'emailLogIds',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutingKey(): string
    {
        return 'resend_emails';
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties(): array
    {
        return [];
    }
}
