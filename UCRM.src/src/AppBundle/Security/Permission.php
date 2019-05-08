<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Security;

/**
 * @Annotation
 */
class Permission
{
    public const PUBLIC = 'public';
    public const GUEST = 'guest';
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DENIED = 'denied';

    public const MODULE_PERMISSIONS = [
        self::VIEW,
        self::EDIT,
        self::DENIED,
    ];

    public const PERMISSIONS_ALL = [
        self::PUBLIC,
        self::GUEST,
        self::VIEW,
        self::EDIT,
        self::DENIED,
    ];

    /**
     * @var string
     */
    private $permission;

    public function __construct(array $options)
    {
        if (in_array($options['value'], self::PERMISSIONS_ALL, true)) {
            $this->permission = $options['value'];
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'Permission can be only %s! Added `%s`',
                    implode(', ', self::PERMISSIONS_ALL),
                    $options['value']
                )
            );
        }
    }

    public function getPermission(): string
    {
        return $this->permission;
    }
}
