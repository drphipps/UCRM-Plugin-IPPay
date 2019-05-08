<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Security;

use AppBundle\Entity\User;
use AppBundle\Entity\UserGroup;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SpecialPermissionsVoter extends Voter
{
    /**
     * @param string $attribute
     *
     * @return bool
     */
    protected function supports($attribute, $subjectName)
    {
        return in_array($attribute, UserGroup::SPECIAL_PERMISSIONS, true);
    }

    /**
     * @param string $attribute can be out of self::ATTRIBUTES
     * @param mixed  $subject   permission being required
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (! $user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        if ($user->getRole() === User::ROLE_SUPER_ADMIN) {
            return true;
        }

        $acl = $user->getGroup() ? $user->getGroup()->getSpecialPermissions() : [];
        foreach ($acl as $perm) {
            if ($perm->isSpecialPermissionSet($attribute, SpecialPermission::ALLOWED)) {
                return true;
            }
        }

        return false;
    }
}
