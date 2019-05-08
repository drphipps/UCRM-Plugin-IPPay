<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Security;

use AppBundle\Entity\User;
use AppBundle\Entity\UserGroupPermission;
use AppBundle\Security\Permission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SchedulingPermissionsVoter extends Voter
{
    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        return in_array($attribute, Permission::MODULE_PERMISSIONS, true)
            && in_array($subject, SchedulingPermissions::PERMISSION_SUBJECTS, true);
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (! $user instanceof User) {
            return false;
        }

        if ($user->getRole() === User::ROLE_SUPER_ADMIN) {
            return true;
        }

        $permissions = $user->getGroup()->getPermissions()->filter(
            function (UserGroupPermission $permission) use ($subject) {
                return $permission->getModuleName() === $subject;
            }
        );
        $permission = $permissions->first();
        if (! $permission) {
            return false;
        }

        return $permission->getPermission() === $attribute
            || (
                $attribute === Permission::VIEW
                && $permission->getPermission() === Permission::EDIT
            );
    }
}
