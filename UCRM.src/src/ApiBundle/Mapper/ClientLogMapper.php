<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Mapper;

use ApiBundle\Exception\UnexpectedTypeException;
use ApiBundle\Map\AbstractMap;
use ApiBundle\Map\ClientLogMap;
use AppBundle\Entity\Client;
use AppBundle\Entity\ClientLog;
use AppBundle\Entity\User;

class ClientLogMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return ClientLogMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return ClientLog::class;
    }

    /**
     * @param ClientLog $entity
     *
     * @throws UnexpectedTypeException
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        if (! $map instanceof ClientLogMap) {
            throw new UnexpectedTypeException($map, ClientLogMap::class);
        }

        $this->mapField($entity, $map, 'message');
        $this->mapField(
            $entity,
            $map,
            'user',
            'userId',
            User::class,
            [
                'role' => User::ADMIN_ROLES,
            ]
        );
        $this->mapField($entity, $map, 'client', 'clientId', Client::class);
        $this->mapField($entity, $map, 'createdDate');
    }

    /**
     * @param ClientLog $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        /** @var ClientLogMap $map */
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'message', $entity->getMessage());
        $this->reflectField($map, 'createdDate', $entity->getCreatedDate());
        $this->reflectField(
            $map,
            'userId',
            $entity->getUser() ? $entity->getUser()->getId() : null
        );
        $this->reflectField($map, 'clientId', $entity->getClient()->getId());
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsDifference(): array
    {
        return [
            'clientId' => 'client',
            'userId' => 'user',
        ];
    }
}
