<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace TicketingBundle\Api\Mapper;

use ApiBundle\Exception\UnexpectedTypeException;
use ApiBundle\Map\AbstractMap;
use ApiBundle\Mapper\AbstractMapper;
use AppBundle\Entity\User;
use TicketingBundle\Api\Map\TicketCommentAttachmentMap;
use TicketingBundle\Api\Map\TicketCommentMap;
use TicketingBundle\Entity\Ticket;
use TicketingBundle\Entity\TicketComment;

class TicketCommentMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return TicketCommentMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return TicketComment::class;
    }

    /**
     * @param TicketComment $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        if (! $map instanceof TicketCommentMap) {
            throw new UnexpectedTypeException($map, TicketCommentMap::class);
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
        $this->mapField($entity, $map, 'emailFromAddress');
        $this->mapField($entity, $map, 'emailFromName');
    }

    /**
     * @param TicketComment $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        /** @var TicketCommentMap $map */
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'ticketId', $entity->getTicket()->getId());
        $this->reflectField($map, 'userId', $entity->getUser() ? $entity->getUser()->getId() : null);
        $this->reflectField($map, 'body', $entity->getBody());
        $this->reflectField($map, 'public', $entity->isPublic());
        $this->reflectField($map, 'createdAt', $entity->getCreatedAt());
        $this->reflectField($map, 'emailFromAddress', $entity->getEmailFromAddress());
        $this->reflectField($map, 'emailFromName', $entity->getEmailFromName());
        foreach ($entity->getAttachments() as $attachment) {
            $attachmentMap = new TicketCommentAttachmentMap();
            $this->reflectField($attachmentMap, 'id', $attachment->getId());
            $this->reflectField($attachmentMap, 'filename', $attachment->getOriginalFilename());
            $this->reflectField($attachmentMap, 'size', $attachment->getSize());
            $this->reflectField($attachmentMap, 'mimeType', $attachment->getMimeType());
            $map->attachments[] = $attachmentMap;
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
}
