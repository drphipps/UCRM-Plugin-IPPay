<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\HeaderNotification\Query;

use AppBundle\Entity\User;
use AppBundle\Security\Permission;
use Doctrine\DBAL\Connection;

class SendByPermissionQuery implements QueryInterface
{
    /**
     * @var mixed[]
     */
    private $parameters;

    public function __construct(string $notificationId, string $permissionName)
    {
        $this->parameters = [
            'notificationId' => $notificationId,
            'adminRoles' => User::ADMIN_ROLES,
            'roleSuperAdmin' => User::ROLE_SUPER_ADMIN,
            'permissionName' => $permissionName,
            'permissionView' => Permission::VIEW,
            'permissionEdit' => Permission::EDIT,
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
          LEFT JOIN
            "user_group_permission" ugp
          ON
            ugp.group_id = u.group_id
            AND ugp.module_name = :permissionName 
          WHERE
            u.role IN (:adminRoles)
            AND (
              u.role = :roleSuperAdmin
              OR ugp.permission = :permissionView
              OR ugp.permission = :permissionEdit
            )
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
