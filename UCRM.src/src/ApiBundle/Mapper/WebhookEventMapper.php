<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Mapper;

use ApiBundle\Exception\UnexpectedTypeException;
use ApiBundle\Map\AbstractMap;
use ApiBundle\Map\ClientMap;
use ApiBundle\Map\WebhookEventMap;
use AppBundle\Entity\WebhookEvent;

class WebhookEventMapper extends AbstractMapper
{
    protected function getMapClassName(): string
    {
        return WebhookEventMap::class;
    }

    protected function getEntityClassName(): string
    {
        return WebhookEvent::class;
    }

    /**
     * @param WebhookEventMap $map
     * @param WebhookEvent    $entity
     *
     * @throws \ApiBundle\Exception\UnexpectedTypeException
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        if (! $map instanceof WebhookEventMap) {
            throw new UnexpectedTypeException($map, WebhookEventMap::class);
        }

        $this->mapField($entity, $map, 'uuid');
        $this->mapField($entity, $map, 'changeType');
        $this->mapField($entity, $map, 'entity');
        $this->mapField($entity, $map, 'entityId');
        $this->mapField($entity, $map, 'eventName');
        $this->mapField($entity, $map, 'extraData');
    }

    /**
     * @param WebhookEvent $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        /** @var ClientMap $map */
        $this->reflectField($map, 'uuid', $entity->getUuid());
        $this->reflectField($map, 'changeType', $entity->getChangeType());
        $this->reflectField($map, 'entity', $entity->getEntity());
        $this->reflectField($map, 'entityId', $entity->getEntityId());
        $this->reflectField($map, 'eventName', $entity->getEventName());
        $this->reflectField($map, 'extraData', $entity->getExtraData());
    }
}
