<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\User;
use AppBundle\Entity\UserGroupPermission;
use AppBundle\Entity\UserGroupSpecialPermission;
use AppBundle\Security\PermissionNames;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Traversable;

class CurrentUserDataProvider
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function getData(): ?array
    {
        $token = $this->tokenStorage->getToken();

        if (! $token) {
            return null;
        }

        $user = $token->getUser();

        if (! $user instanceof User) {
            return null;
        }

        $userGroup = $user->getGroup();
        $client = $user->getClient();

        return [
            'userId' => $user->getId(),
            'username' => $user->getUsername(),
            'isClient' => (bool) $client,
            'clientId' => $client ? $client->getId() : null,
            'userGroup' => $userGroup ? $userGroup->getName() : null,
            'specialPermissions' => $userGroup ? $this->serializeSpecialPermissions($userGroup->getSpecialPermissions()) : null,
            'permissions' => $userGroup ? $this->serializePermissions($userGroup->getPermissions()) : null,
        ];
    }

    /**
     * @return string[]
     */
    private function serializePermissions(Traversable $permissions): array
    {
        $serialized = [];

        /** @var UserGroupPermission $permission */
        foreach ($permissions as $permission) {
            $serialized[PermissionNames::PERMISSION_SYSTEM_NAMES[$permission->getModuleName()]] = $permission->getPermission();
        }

        return $serialized;
    }

    /**
     * @return string[]
     */
    private function serializeSpecialPermissions(Traversable $permissions): array
    {
        $serialized = [];

        /** @var UserGroupSpecialPermission $permission */
        foreach ($permissions as $permission) {
            $serialized[PermissionNames::SPECIAL_PERMISSIONS_SYSTEM_NAMES[$permission->getModuleName()]] = $permission->getPermission();
        }

        return $serialized;
    }
}
