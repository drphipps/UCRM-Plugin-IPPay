<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace SchedulingBundle\Api\Mapper;

use ApiBundle\Exception\UnexpectedTypeException;
use ApiBundle\Map\AbstractMap;
use ApiBundle\Mapper\AbstractMapper;
use AppBundle\Entity\User;
use SchedulingBundle\Api\Map\JobCommentMap;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Entity\JobComment;

class JobCommentMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return JobCommentMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return JobComment::class;
    }

    /**
     * @param JobComment $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        if (! $map instanceof JobCommentMap) {
            throw new UnexpectedTypeException($map, JobCommentMap::class);
        }

        $this->mapField($entity, $map, 'job', 'jobId', Job::class);
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
        $this->mapField($entity, $map, 'createdDate');
        $this->mapField($entity, $map, 'message');
    }

    /**
     * @param JobComment $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        /** @var JobCommentMap $map */
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'jobId', $entity->getJob() ? $entity->getJob()->getId() : null);
        $this->reflectField($map, 'userId', $entity->getUser() ? $entity->getUser()->getId() : null);
        $this->reflectField($map, 'createdDate', $entity->getCreatedDate());
        $this->reflectField($map, 'message', $entity->getMessage());
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsDifference(): array
    {
        return [
            'job' => 'jobId',
            'user' => 'userId',
        ];
    }
}
