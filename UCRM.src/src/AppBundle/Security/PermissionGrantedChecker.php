<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Security;

use ApiBundle\Security\AppKeyUser;
use AppBundle\Entity\AppKey;
use AppBundle\Entity\User;
use AppBundle\Entity\UserGroup;
use Doctrine\Common\Annotations\Reader;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

class PermissionGrantedChecker
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var Reader
     */
    private $annotationReader;

    /**
     * @var UserInterface|null
     */
    protected $user;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        Reader $annotationReader
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->annotationReader = $annotationReader;
    }

    public function isGrantedSpecial(string $permissionName): bool
    {
        return $this->isGranted(SpecialPermission::ALLOWED, $permissionName);
    }

    public function isGranted(string $permissionLevel, string $permissionName): bool
    {
        if ($permissionLevel === Permission::PUBLIC) {
            return true;
        }

        try {
            $user = $this->getUser();
        } catch (AccessDeniedException $exception) {
            return false;
        }

        if ($user instanceof AppKeyUser) {
            $keyType = $user->getType();

            return $permissionLevel === SpecialPermission::ALLOWED
                || $permissionLevel === Permission::GUEST
                || $permissionLevel === Permission::VIEW && in_array($keyType, [AppKey::TYPE_READ, AppKey::TYPE_WRITE], true)
                || $permissionLevel === Permission::EDIT && in_array($keyType, [AppKey::TYPE_WRITE], true);
        }

        assert($user instanceof User);

        if (
            $user->getRole() === User::ROLE_SUPER_ADMIN
            || $permissionLevel === Permission::GUEST
        ) {
            return true;
        }

        if (in_array($permissionName, UserGroup::PERMISSION_MODULES, true)) {
            $group = $user->getGroup();

            if (! $group) {
                return false;
            }

            $permissions = $group->getPermissions();

            foreach ($permissions as $permission) {
                if ($permission->getModuleName() === $permissionName) {
                    return ($permission->getPermission() === $permissionLevel)
                        || ($permissionLevel === Permission::VIEW && $permission->getPermission() === Permission::EDIT);
                }
            }

            return false;
        }

        if (class_exists($permissionName) && is_subclass_of($permissionName, Controller::class)) {
            $inheritAnnotation = $this->annotationReader->getClassAnnotation(
                new \ReflectionClass($permissionName),
                PermissionControllerName::class
            );
            if ($inheritAnnotation instanceof PermissionControllerName) {
                if ($inheritAnnotation->getController() === $permissionName) {
                    throw new \Exception(
                        sprintf(
                            'PermissionControllerName class cannot be the same as the controller itself (%s).',
                            $permissionName
                        )
                    );
                }

                return $this->isGranted($permissionLevel, $inheritAnnotation->getController());
            }

            // This controller can not be accessed
            return false;
        }

        if (in_array($permissionName, UserGroup::SPECIAL_PERMISSIONS, true)) {
            return $this->authorizationChecker->isGranted($permissionName);
        }

        return $this->authorizationChecker->isGranted($permissionLevel, $permissionName);
    }

    /**
     * @throws AccessDeniedException
     */
    public function denyAccessUnlessGranted(string $permissionLevel, string $permissionName): void
    {
        if (! $this->isGranted($permissionLevel, $permissionName)) {
            throw new AccessDeniedException("You don't have permissions.");
        }
    }

    /**
     * @return User|AppKeyUser|UserInterface
     *
     * @throws AccessDeniedException
     */
    private function getUser(): UserInterface
    {
        if (! $this->user) {
            $token = $this->tokenStorage->getToken();
            if ($token && $token->getUser() instanceof UserInterface) {
                $this->user = $token->getUser();
            } else {
                throw new AccessDeniedException('You need to be logged in.');
            }
        }

        return $this->user;
    }
}
