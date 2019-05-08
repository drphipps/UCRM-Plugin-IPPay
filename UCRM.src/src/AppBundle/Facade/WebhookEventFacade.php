<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use ApiBundle\DependencyInjection\MapperResolver;
use AppBundle\Entity\WebhookEvent;
use AppBundle\Interfaces\WebhookRequestableInterface;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;

class WebhookEventFacade
{
    /**
     * @var MapperResolver
     */
    private $mapperResolver;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(
        MapperResolver $mapperResolver,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    ) {
        $this->mapperResolver = $mapperResolver;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    public function handleCreateFromData(array $data): WebhookEvent
    {
        if (
            ! array_key_exists('entityClass', $data)
            || ! array_key_exists('entityId', $data)
            || ! array_key_exists('changeType', $data)
            || ! array_key_exists('extraData', $data)
        ) {
            throw new \InvalidArgumentException('Data does not contain required fields.');
        }
        $webhookEvent = new WebhookEvent();
        $webhookEvent->setEventName($data['eventName'] ?? '');
        $webhookEvent->setEntity((string) $data['entityClass']);
        $webhookEvent->setEntityId($data['entityId'] === null ? null : (int) $data['entityId']);
        $webhookEvent->setChangeType((string) $data['changeType']);
        $webhookEvent->setExtraData($data['extraData']);
        $this->entityManager->persist($webhookEvent);
        $this->entityManager->flush();

        return $webhookEvent;
    }

    public function getDataFromEntity(string $className, ?object $entity): ?object
    {
        $mapper = $this->mapperResolver->get($className);
        if (! $mapper || ! $entity) {
            return null;
        }

        return $mapper->reflect($entity);
    }

    public function getJsonFromEvent(WebhookRequestableInterface $event, ?object $previousEntityData): string
    {
        $eventArray = [
            'eventName' => $event->getEventName(),
            'entityClass' => $event->getWebhookEntityClass(),
            'changeType' => $event->getWebhookChangeType(),
            'entityId' => $event->getWebhookEntityId(),
            'extraData' => [],
        ];
        if (! $eventArray['entityId']) {
            $entity = $event->getWebhookEntity();
            if ($entity && method_exists($entity, 'getId')) {
                $eventArray['entityId'] = $entity->getId();
            }
        }
        $eventArray['extraData']['entity'] = $this->getDataFromEntity(
            $event->getWebhookEntityClass(),
            $event->getWebhookEntity()
        );
        // We cannot extract meaningful data on previous entity state here:
        // might have already been modified by Em, as we're likely post-commit.
        // Use previously serialized data, if provided from outside.
        $eventArray['extraData']['entityBeforeEdit'] = $previousEntityData;

        return $this->serializer->serialize($eventArray, 'json');
    }
}
