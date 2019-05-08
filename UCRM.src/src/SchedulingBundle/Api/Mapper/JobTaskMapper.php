<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace SchedulingBundle\Api\Mapper;

use ApiBundle\Exception\UnexpectedTypeException;
use ApiBundle\Map\AbstractMap;
use ApiBundle\Mapper\AbstractMapper;
use SchedulingBundle\Api\Map\JobTaskMap;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Entity\JobTask;

class JobTaskMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return JobTaskMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return JobTask::class;
    }

    /**
     * @param JobTask $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        if (! $map instanceof JobTaskMap) {
            throw new UnexpectedTypeException($map, JobTaskMap::class);
        }

        $this->mapField($entity, $map, 'job', 'jobId', Job::class);
        $this->mapField($entity, $map, 'label');
        $this->mapField($entity, $map, 'closed');
    }

    /**
     * @param JobTask $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        /** @var JobTaskMap $map */
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'jobId', $entity->getJob()->getId());
        $this->reflectField($map, 'label', $entity->getLabel());
        $this->reflectField($map, 'closed', $entity->isClosed());
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsDifference(): array
    {
        return [
            'job' => 'jobId',
        ];
    }
}
