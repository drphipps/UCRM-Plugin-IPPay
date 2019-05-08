<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Api\Mapper;

use ApiBundle\Exception\UnexpectedTypeException;
use ApiBundle\Map\AbstractMap;
use ApiBundle\Mapper\AbstractMapper;
use AppBundle\Entity\User;
use TicketingBundle\Api\Map\TicketActivityClientAssignmentMap;
use TicketingBundle\Api\Map\TicketActivityCommentMap;
use TicketingBundle\Api\Map\TicketActivityJobAssignmentMap;
use TicketingBundle\Api\Map\TicketActivityMap;
use TicketingBundle\Api\Map\TicketActivityStatusChangeMap;
use TicketingBundle\Api\Map\TicketActivityUserAssignmentMap;
use TicketingBundle\Api\Map\TicketCommentAttachmentMap;
use TicketingBundle\Entity\Ticket;
use TicketingBundle\Entity\TicketActivity;
use TicketingBundle\Entity\TicketClientAssignment;
use TicketingBundle\Entity\TicketComment;
use TicketingBundle\Entity\TicketJobAssignment;
use TicketingBundle\Entity\TicketStatusChange;
use TicketingBundle\Entity\TicketUserAssignment;

class TicketActivityMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return TicketActivityMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return TicketActivity::class;
    }

    /**
     * @param TicketActivity $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        if (! $map instanceof TicketActivityMap) {
            throw new UnexpectedTypeException($map, TicketActivityMap::class);
        }

        $this->mapField($entity, $map, 'ticket', 'ticketId', Ticket::class);
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
        $this->mapField($entity, $map, 'body');
        $this->mapField($entity, $map, 'public');
        $this->mapField($entity, $map, 'createdAt');
    }

    /**
     * @param TicketActivity $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        assert($map instanceof TicketActivityMap);

        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'ticketId', $entity->getTicket()->getId());
        $this->reflectField($map, 'userId', $entity->getUser() ? $entity->getUser()->getId() : null);
        $this->reflectField($map, 'public', $entity->isPublic());
        $this->reflectField($map, 'createdAt', $entity->getCreatedAt());

        switch (true) {
            case $entity instanceof TicketComment:
                $this->reflectActivityComment($entity, $map);
                break;
            case $entity instanceof TicketUserAssignment:
                $this->reflectActivityUserAssignment($entity, $map);
                break;
            case $entity instanceof TicketClientAssignment:
                $this->reflectActivityClientAssignment($entity, $map);
                break;
            case $entity instanceof TicketStatusChange:
                $this->reflectActivityStatusChange($entity, $map);
                break;
            case $entity instanceof TicketJobAssignment:
                $this->reflectActivityJobAssignment($entity, $map);
                break;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsDifference(): array
    {
        return [
            'ticket' => 'ticketId',
            'user' => 'userId',
        ];
    }

    private function reflectActivityComment(TicketComment $entity, TicketActivityMap $map): void
    {
        $activityMap = new TicketActivityCommentMap();
        foreach ($entity->getAttachments() as $attachment) {
            $attachmentMap = new TicketCommentAttachmentMap();
            $this->reflectField($attachmentMap, 'id', $attachment->getId());
            $this->reflectField($attachmentMap, 'filename', $attachment->getOriginalFilename());
            $this->reflectField($attachmentMap, 'size', $attachment->getSize());
            $this->reflectField($attachmentMap, 'mimeType', $attachment->getMimeType());
            $activityMap->attachments[] = $attachmentMap;
        }
        $this->reflectField($activityMap, 'body', $entity->getBody());
        $this->reflectField($activityMap, 'emailFromAddress', $entity->getEmailFromAddress());
        $this->reflectField($activityMap, 'emailFromName', $entity->getEmailFromName());
        $this->reflectField($map, 'type', TicketActivityMap::TYPE_COMMENT);
        $map->comment = $activityMap;
    }

    private function reflectActivityUserAssignment(TicketUserAssignment $entity, TicketActivityMap $map): void
    {
        $activityMap = new TicketActivityUserAssignmentMap();
        $this->reflectField(
            $activityMap,
            'assignedUserId',
            $entity->getAssignedUser() ? $entity->getAssignedUser()->getId() : null
        );
        $this->reflectField($map, 'type', TicketActivityMap::TYPE_ASSIGNMENT);
        $map->assignment = $activityMap;
    }

    private function reflectActivityClientAssignment(TicketClientAssignment $entity, TicketActivityMap $map): void
    {
        $activityMap = new TicketActivityClientAssignmentMap();
        $this->reflectField(
            $activityMap,
            'assignedClientId',
            $entity->getAssignedClient() ? $entity->getAssignedClient()->getId() : null
        );
        $this->reflectField($map, 'type', TicketActivityMap::TYPE_ASSIGNMENT_CLIENT);
        $map->clientAssignment = $activityMap;
    }

    private function reflectActivityStatusChange(TicketStatusChange $entity, TicketActivityMap $map): void
    {
        $activityMap = new TicketActivityStatusChangeMap();
        $this->reflectField(
            $activityMap,
            'status',
            $entity->getStatus()
        );
        $this->reflectField($map, 'type', TicketActivityMap::TYPE_STATUS_CHANGE);
        $map->statusChange = $activityMap;
    }

    private function reflectActivityJobAssignment(TicketJobAssignment $entity, TicketActivityMap $map): void
    {
        $activityMap = new TicketActivityJobAssignmentMap();
        $this->reflectField(
            $activityMap,
            'assignedJobId',
            $entity->getAssignedJob() ? $entity->getAssignedJob()->getId() : null
        );
        $this->reflectField($map, 'type', TicketActivityMap::TYPE_ASSIGNMENT_JOB);
        $map->jobAssignment = $activityMap;
    }
}
