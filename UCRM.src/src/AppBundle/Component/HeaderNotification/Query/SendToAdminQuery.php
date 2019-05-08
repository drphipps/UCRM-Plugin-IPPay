<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\HeaderNotification\Query;

class SendToAdminQuery implements QueryInterface
{
    /**
     * @var mixed[]
     */
    private $parameters;

    public function __construct(string $notificationId, int $userId)
    {
        $this->parameters = [
            'notificationId' => $notificationId,
            'userId' => $userId,
        ];
    }

    public function getQuery(): string
    {
        return '
          INSERT INTO
            header_notification_status (id, header_notification_id, user_id)
          SELECT
            uuid_generate_v4(), :notificationId, :userId
        ';
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getParameterTypes(): array
    {
        return [];
    }
}
