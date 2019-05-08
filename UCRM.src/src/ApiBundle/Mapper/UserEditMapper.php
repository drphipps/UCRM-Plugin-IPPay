<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Mapper;

use ApiBundle\Map\AbstractMap;
use ApiBundle\Map\UserEditMap;
use AppBundle\Entity\User;

class UserEditMapper extends AbstractMapper
{
    protected function getMapClassName(): string
    {
        return UserEditMap::class;
    }

    protected function getEntityClassName(): string
    {
        return User::class;
    }

    /**
     * @param User $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        $this->mapField($entity, $map, 'email');
        $this->mapField($entity, $map, 'firstName');
        $this->mapField($entity, $map, 'lastName');
        $this->mapField($entity, $map, 'username');
        $this->mapField($entity, $map, 'avatarColor');
        $this->mapField($entity, $map, 'plainPassword', 'password');
    }

    /**
     * @param User $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        throw new \LogicException('Only used for updating, never for GET.');
    }

    public function getFieldsDifference(): array
    {
        return [
            'plainPassword' => 'password',
        ];
    }
}
