<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace SchedulingBundle\Api\Mapper;

use ApiBundle\Exception\UnexpectedTypeException;
use ApiBundle\Map\AbstractMap;
use ApiBundle\Mapper\AbstractMapper;
use SchedulingBundle\Api\Map\JobAttachmentMap;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Entity\JobAttachment;

class JobAttachmentMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return JobAttachmentMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return JobAttachment::class;
    }

    /**
     * @param JobAttachment $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        if (! $map instanceof JobAttachmentMap) {
            throw new UnexpectedTypeException($map, JobAttachmentMap::class);
        }

        $this->mapField($entity, $map, 'job', 'jobId', Job::class);
        $this->mapField($entity, $map, 'originalFilename', 'filename');
    }

    /**
     * @param JobAttachment $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        /** @var JobAttachmentMap $map */
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'jobId', $entity->getJob()->getId());
        $this->reflectField($map, 'mimeType', $entity->getMimeType());
        $this->reflectField($map, 'size', $entity->getSize());
        $this->reflectField($map, 'filename', $entity->getOriginalFilename());
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsDifference(): array
    {
        return [
            'job' => 'jobId',
            'filename' => 'originalFilename',
        ];
    }
}
