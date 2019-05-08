<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace SchedulingBundle\Api\Mapper;

use ApiBundle\Exception\UnexpectedTypeException;
use ApiBundle\Map\AbstractMap;
use ApiBundle\Mapper\AbstractMapper;
use AppBundle\Entity\Client;
use AppBundle\Entity\User;
use SchedulingBundle\Api\Map\JobAttachmentMap;
use SchedulingBundle\Api\Map\JobMap;
use SchedulingBundle\Api\Map\JobTaskMap;
use SchedulingBundle\Entity\Job;

class JobMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return JobMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return Job::class;
    }

    /**
     * @param Job $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        if (! $map instanceof JobMap) {
            throw new UnexpectedTypeException($map, JobMap::class);
        }

        $this->mapField($entity, $map, 'title');
        $this->mapField($entity, $map, 'description');
        $this->mapField(
            $entity,
            $map,
            'assignedUser',
            'assignedUserId',
            User::class,
            [
                'role' => User::ADMIN_ROLES,
            ]
        );
        $this->mapField($entity, $map, 'client', 'clientId', Client::class);
        $this->mapField($entity, $map, 'date');
        $this->mapField($entity, $map, 'duration');
        $this->mapField($entity, $map, 'status');
        $this->mapField($entity, $map, 'address');
        $this->mapField($entity, $map, 'gpsLat');
        $this->mapField($entity, $map, 'gpsLon');
    }

    /**
     * @param Job $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        /** @var JobMap $map */
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'title', $entity->getTitle());
        $this->reflectField($map, 'description', $entity->getDescription());
        $this->reflectField($map, 'date', $entity->getDate());
        $this->reflectField($map, 'duration', $entity->getDuration());
        $this->reflectField($map, 'status', $entity->getStatus());
        $this->reflectField($map, 'address', $entity->getAddress());
        $this->reflectField($map, 'gpsLat', $entity->getGpsLat());
        $this->reflectField($map, 'gpsLon', $entity->getGpsLon());

        if ($map instanceof JobMap) {
            $this->reflectField(
                $map,
                'assignedUserId',
                $entity->getAssignedUser() ? $entity->getAssignedUser()->getId() : null
            );
            $this->reflectField($map, 'clientId', $entity->getClient() ? $entity->getClient()->getId() : null);

            foreach ($entity->getAttachments() as $attachment) {
                $attachmentMap = new JobAttachmentMap();
                $this->reflectField($attachmentMap, 'id', $attachment->getId());
                $this->reflectField($attachmentMap, 'jobId', $attachment->getJob() ? $attachment->getJob()->getId() : null);
                $this->reflectField($attachmentMap, 'filename', $attachment->getOriginalFilename());
                $this->reflectField($attachmentMap, 'size', $attachment->getSize());
                $this->reflectField($attachmentMap, 'mimeType', $attachment->getMimeType());

                $map->attachments[] = $attachmentMap;
            }

            foreach ($entity->getTasks() as $task) {
                $taskMap = new JobTaskMap();
                $this->reflectField($taskMap, 'id', $task->getId());
                $this->reflectField($taskMap, 'jobId', $task->getJob()->getId());
                $this->reflectField($taskMap, 'label', $task->getLabel());
                $this->reflectField($taskMap, 'closed', $task->isClosed());

                $map->tasks[] = $taskMap;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsDifference(): array
    {
        return [
            'assignedUser' => 'assignedUserId',
            'client' => 'clientId',
        ];
    }
}
