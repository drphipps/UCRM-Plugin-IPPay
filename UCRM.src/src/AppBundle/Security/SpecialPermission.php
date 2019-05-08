<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Security;

use AppBundle\Entity\UserGroup;

/**
 * @Annotation
 */
class SpecialPermission
{
    public const ALLOWED = 'allow';
    public const DENIED = 'deny';

    /**
     * @see UserGroup::SPECIAL_PERMISSIONS
     * @see PermissionNames::SPECIAL_PERMISSIONS_SYSTEM_NAMES
     * @see PermissionNames::SPECIAL_PERMISSIONS_HUMAN_NAMES
     */
    public const FINANCIAL_OVERVIEW = 'FINANCIAL_OVERVIEW';
    public const CLIENT_ACCOUNT_STANDING = 'CLIENT_ACCOUNT_STANDING';
    public const CLIENT_EXPORT = 'CLIENT_EXPORT';
    public const CLIENT_IMPERSONATION = 'CLIENT_IMPERSONATION';
    public const CLIENT_LOG_EDIT = 'CLIENT_LOG_EDIT';
    public const SHOW_DEVICE_PASSWORDS = 'SHOW_DEVICE_PASSWORDS';
    public const JOB_COMMENT_EDIT = 'JOB_COMMENT_EDIT';
    public const PAYMENT_CREATE = 'PAYMENT_CREATE';

    /**
     * @var string
     */
    private $permission;

    /**
     * @param array $options
     */
    public function __construct($options)
    {
        if (in_array($options['value'], [self::ALLOWED, self::DENIED], true)) {
            $this->permission = $options['value'];
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'Permission can be only %s! Added `%s`',
                    implode(', ', [self::ALLOWED, self::DENIED]),
                    $options['value']
                )
            );
        }
    }

    /**
     * @return string
     */
    public function getPermission()
    {
        return $this->permission;
    }
}
