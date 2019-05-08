<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Report;

use AppBundle\Entity\User;
use Nette\Utils\Json;
use RabbitMqBundle\MessageInterface;

class ReportDataUsageMessage implements MessageInterface
{
    /**
     * @var User
     */
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getProducer(): string
    {
        return 'report_data_usage_generate';
    }

    public function getBody(): string
    {
        return Json::encode(
            [
                'user' => $this->user->getId(),
            ]
        );
    }

    public function getBodyProperties(): array
    {
        return [
            'user',
        ];
    }

    public function getRoutingKey(): string
    {
        return 'report_data_usage_generate';
    }

    public function getProperties(): array
    {
        return [];
    }
}
