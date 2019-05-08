<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Mapper;

use ApiBundle\Map\AbstractMap;
use ApiBundle\Map\UserMap;
use AppBundle\Entity\User;

class UserMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return UserMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return User::class;
    }

    /**
     * @param User $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        throw new \LogicException('Only used for getting, never for updating.');
    }

    /**
     * @param User $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'email', $entity->getEmail());
        $this->reflectField($map, 'firstName', $entity->getFirstName());
        $this->reflectField($map, 'lastName', $entity->getLastName());
        $this->reflectField($map, 'username', $entity->getUsername());
        $this->reflectField($map, 'avatarColor', $entity->getAvatarColor());
    }
}
