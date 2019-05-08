<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\HeaderNotification\Query;

use AppBundle\Entity\User;
use Doctrine\DBAL\Connection;

class SendToAllAdminsQuery implements QueryInterface
{
    /**
     * @var mixed[]
     */
    private $parameters;

    public function __construct(string $notificationId)
    {
        $this->parameters = [
            'notificationId' => $notificationId,
            'adminRoles' => User::ADMIN_ROLES,
        ];
    }

    public function getQuery(): string
    {
        return '
          INSERT INTO
            header_notification_status (id, header_notification_id, user_id)
          SELECT
            uuid_generate_v4(), :notificationId, u."user_id"
          FROM
            "user" u
          WHERE
            u.role IN (:adminRoles)
        ';
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getParameterTypes(): array
    {
        return [
            'adminRoles' => Connection::PARAM_STR_ARRAY,
        ];
    }
}
