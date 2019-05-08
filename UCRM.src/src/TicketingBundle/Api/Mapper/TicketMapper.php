<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace TicketingBundle\Api\Mapper;

use ApiBundle\Exception\UnexpectedTypeException;
use ApiBundle\Map\AbstractMap;
use ApiBundle\Mapper\AbstractMapper;
use AppBundle\Entity\Client;
use AppBundle\Entity\User;
use SchedulingBundle\Entity\Job;
use TicketingBundle\Api\Map\TicketActivityClientAssignmentMap;
use TicketingBundle\Api\Map\TicketActivityCommentMap;
use TicketingBundle\Api\Map\TicketActivityJobAssignmentMap;
use TicketingBundle\Api\Map\TicketActivityMap;
use TicketingBundle\Api\Map\TicketActivityStatusChangeMap;
use TicketingBundle\Api\Map\TicketActivityUserAssignmentMap;
use TicketingBundle\Api\Map\TicketCommentAttachmentMap;
use TicketingBundle\Api\Map\TicketMap;
use TicketingBundle\Entity\Ticket;
use TicketingBundle\Entity\TicketClientAssignment;
use TicketingBundle\Entity\TicketComment;
use TicketingBundle\Entity\TicketGroup;
use TicketingBundle\Entity\TicketJobAssignment;
use TicketingBundle\Entity\TicketStatusChange;
use TicketingBundle\Entity\TicketUserAssignment;

class TicketMapper extends AbstractMapper
{
    /**
     * {@inheritdoc}
     */
    protected function getMapClassName(): string
    {
        return TicketMap::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName(): string
    {
        return Ticket::class;
    }

    /**
     * @param Ticket $entity
     */
    protected function doMap(AbstractMap $map, $entity): void
    {
        if (! $map instanceof TicketMap) {
            throw new UnexpectedTypeException($map, TicketMap::class);
        }

        $this->mapField($entity, $map, 'subject');
        $this->mapField($entity, $map, 'client', 'clientId', Client::class);
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
        $this->mapField($entity, $map, 'createdAt');
        $this->mapField($entity, $map, 'group', 'assignedGroupId', TicketGroup::class);
        $this->mapField($entity, $map, 'status');
        $this->mapField($entity, $map, 'public');
        $this->mapField($entity, $map, 'emailFromAddress');
        $this->mapField($entity, $map, 'emailFromName');

        /** @var TicketActivityMap $activity */
        foreach ($map->activity as $activity) {
            if (! $activity->comment) {
                continue;
            }

            $ticketComment = new TicketComment();
            $ticketComment->setBody($activity->comment->body);
            $ticketComment->setCreatedAt($map->createdAt ?? (clone $entity->getCreatedAt()));
            $ticketComment->setTicket($entity);
            $ticketComment->setPublic($activity->public ?? true);
            $ticketComment->setEmailFromAddress($activity->comment->emailFromAddress);
            $ticketComment->setEmailFromName($activity->comment->emailFromName);
            $entity->addActivity($ticketComment);
        }

        if (null !== $map->assignedJobIds) {
            $jobRepository = $this->entityManager->getRepository(Job::class);
            foreach ($map->assignedJobIds as $jobId) {
                $job = $jobRepository->find($jobId);
                if ($job) {
                    $entity->addJob($job);
                } else {
                    $this->errorCollector->add('assignedJobIds', sprintf('Job with id %d not found.', $jobId));
                }
            }
        }
    }

    /**
     * @param Ticket $entity
     */
    protected function doReflect($entity, AbstractMap $map, array $options = []): void
    {
        /** @var TicketMap $map */
        $this->reflectField($map, 'assignedGroupId', $entity->getGroup() ? $entity->getGroup()->getId() : null);
        $this->reflectField($map, 'id', $entity->getId());
        $this->reflectField($map, 'subject', $entity->getSubject());
        $this->reflectField($map, 'clientId', $entity->getClient() ? $entity->getClient()->getId() : null);
        $this->reflectField(
            $map,
            'assignedUserId',
            $entity->getAssignedUser() ? $entity->getAssignedUser()->getId() : null
        );
        $this->reflectField($map, 'createdAt', $entity->getCreatedAt());
        $this->reflectField($map, 'status', $entity->getStatus());
        $this->reflectField($map, 'public', $entity->isPublic());
        $this->reflectField($map, 'emailFromAddress', $entity->getEmailFromAddress());
        $this->reflectField($map, 'emailFromName', $entity->getEmailFromName());
        $this->reflectField($map, 'lastActivity', $entity->getLastActivity());
        $this->reflectField($map, 'lastCommentAt', $entity->getLastCommentAt());
        $this->reflectField($map, 'isLastActivityByClient', $entity->isLastActivityByClient());

        foreach ($entity->getActivity() as $ticketActivity) {
            if (
                ($options['publicActivity'] ?? null) !== null
                && $ticketActivity->isPublic() !== $options['publicActivity']
            ) {
                continue;
            }

            $activityMap = new TicketActivityMap();
            $this->reflectField($activityMap, 'id', $ticketActivity->getId());
            $this->reflectField($activityMap, 'ticketId', $ticketActivity->getTicket()->getId());
            $this->reflectField(
                $activityMap,
                'userId',
                $ticketActivity->getUser() ? $ticketActivity->getUser()->getId() : null
            );
            $this->reflectField($activityMap, 'public', $ticketActivity->isPublic());
            $this->reflectField($activityMap, 'createdAt', $ticketActivity->getCreatedAt());

            if ($ticketActivity instanceof TicketComment) {
                $commentMap = new TicketActivityCommentMap();
                $this->reflectField($commentMap, 'body', $ticketActivity->getBody());
                $this->reflectField($commentMap, 'emailFromAddress', $ticketActivity->getEmailFromAddress());
                $this->reflectField($commentMap, 'emailFromName', $ticketActivity->getEmailFromName());

                foreach ($ticketActivity->getAttachments() as $attachment) {
                    $attachmentMap = new TicketCommentAttachmentMap();
                    $this->reflectField($attachmentMap, 'id', $attachment->getId());
                    $this->reflectField($attachmentMap, 'filename', $attachment->getOriginalFilename());
                    $this->reflectField($attachmentMap, 'size', $attachment->getSize());
                    $this->reflectField($attachmentMap, 'mimeType', $attachment->getMimeType());
                    $commentMap->attachments[] = $attachmentMap;
                }
                $this->reflectField($activityMap, 'comment', $commentMap);
                $activityMap->type = TicketActivityMap::TYPE_COMMENT;
            }

            if ($ticketActivity instanceof TicketUserAssignment) {
                $assignmentMap = new TicketActivityUserAssignmentMap();
                $this->reflectField(
                    $assignmentMap,
                    'assignedUserId',
                    $ticketActivity->getAssignedUser() ? $ticketActivity->getAssignedUser()->getId() : null
                );
                $this->reflectField($activityMap, 'assignment', $assignmentMap);
                $activityMap->type = TicketActivityMap::TYPE_ASSIGNMENT;
            }

            if ($ticketActivity instanceof TicketClientAssignment) {
                $assignmentMap = new TicketActivityClientAssignmentMap();
                $this->reflectField(
                    $assignmentMap,
                    'assignedClientId',
                    $ticketActivity->getAssignedClient() ? $ticketActivity->getAssignedClient()->getId() : null
                );
                $this->reflectField($activityMap, 'clientAssignment', $assignmentMap);
                $activityMap->type = TicketActivityMap::TYPE_ASSIGNMENT_CLIENT;
            }

            if ($ticketActivity instanceof TicketStatusChange) {
                $assignmentMap = new TicketActivityStatusChangeMap();
                $this->reflectField(
                    $assignmentMap,
                    'status',
                    $ticketActivity->getStatus()
                );
                $this->reflectField($activityMap, 'statusChange', $assignmentMap);
                $activityMap->type = TicketActivityMap::TYPE_STATUS_CHANGE;
            }

            if ($ticketActivity instanceof TicketJobAssignment) {
                $assignmentMap = new TicketActivityJobAssignmentMap();
                $this->reflectField(
                    $assignmentMap,
                    'assignedJobId',
                    $ticketActivity->getAssignedJob() ? $ticketActivity->getAssignedJob()->getId() : null
                );
                $this->reflectField(
                    $assignmentMap,
                    'type',
                    $ticketActivity->getType()
                );

                $this->reflectField($activityMap, 'jobAssignment', $assignmentMap);
                $activityMap->type = TicketActivityMap::TYPE_ASSIGNMENT_JOB;
            }

            $map->activity[] = $activityMap;
        }

        foreach ($entity->getJobs() as $job) {
            $map->assignedJobIds[] = $job->getId();
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
            'group' => 'assignedGroupId',
        ];
    }
}
