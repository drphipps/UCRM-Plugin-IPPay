<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Subscriber\Doctrine;

use AppBundle\Entity\EntityLog;
use AppBundle\Entity\LoggableInterface;
use AppBundle\Entity\Option;
use AppBundle\Entity\ParentLoggableInterface;
use AppBundle\Entity\SoftDeleteLoggableInterface;
use AppBundle\Entity\TariffPeriod;
use AppBundle\Entity\User;
use AppBundle\Entity\UserGroupPermission;
use AppBundle\Security\PermissionNames;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class LogSubscriber implements EventSubscriber
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush,
        ];
    }

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();
        $scheduledInsertions = $uow->getScheduledEntityInsertions();
        $entitiesBeingInserted = [];

        foreach ($scheduledInsertions as $entity) {
            if ($entity instanceof LoggableInterface) {
                // @todo: FIXME $entity->getId() is still null at this point.
                $entitiesBeingInserted[get_class($entity)][$entity->getId()] = true;
            }
        }

        foreach ($scheduledInsertions as $entity) {
            if ($entity instanceof LoggableInterface) {
                $parentEntity = $entity->getLogParentEntity();

                if (! $parentEntity ||
                    ! isset($entitiesBeingInserted[get_class($parentEntity)][$parentEntity->getId()])
                ) {
                    $logMessage = $this->getLogMessage($entity, EntityLog::INSERT);
                    $this->log($em, $uow, $entity, EntityLog::INSERT, $logMessage);
                }
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->prepareChangeSet($em, $uow, $uow->getEntityChangeSet($entity), $entity);
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $logMessage = $this->getLogMessage($entity, EntityLog::DELETE);
            $this->log($em, $uow, $entity, EntityLog::DELETE, $logMessage);
        }
    }

    /**
     * @param array|string|null $logMessage
     */
    protected function log(
        EntityManager $em,
        UnitOfWork $uow,
        $entity,
        int $changeType,
        $logMessage = null
    ): void {
        if ($this->removedFromLog($entity, $changeType)) {
            return;
        }

        $user = $this->getUserEntity($em);

        $log = new EntityLog();
        $log->setCreatedDate(new \DateTime());
        $log->setLog(serialize($logMessage));
        $log->setUser($user);
        $log->setChangeType($changeType);
        $log->setEntity(get_class($entity));
        $log->setUserType($this->getUserType($user));

        if (get_class($entity) === UserGroupPermission::class) {
            $log->setEntityId($entity->getGroupPermissionId());
        } else {
            $log->setEntityId($entity->getId());
        }

        if (
            method_exists($entity, 'getLogClient')
            && $entity->getLogClient()
            && ! $em->getUnitOfWork()->isScheduledForDelete($entity->getLogClient())
            && $em->contains($entity->getLogClient())
        ) {
            $log->setClient($entity->getLogClient());
        }

        if (method_exists($entity, 'getLogSite')) {
            $log->setSite($entity->getLogSite());
        }

        if (method_exists($entity, 'getLogParentEntity') && $entity->getLogParentEntity()) {
            $log->setParentEntity(get_class($entity->getLogParentEntity()));
            $log->setParentEntityId($entity->getLogParentEntity()->getId());
        }

        $uow->persist($log);
        $uow->computeChangeSet($em->getClassMetadata(EntityLog::class), $log);
    }

    /**
     * Disable log for entity.
     *
     * @param object $entity
     */
    protected function removedFromLog($entity, int $changeType): bool
    {
        if (in_array(get_class($entity), [UserGroupPermission::class, TariffPeriod::class], true)) {
            return in_array($changeType, [EntityLog::INSERT, EntityLog::DELETE], true);
        }

        return ! $entity instanceof LoggableInterface;
    }

    protected function getUserEntity(EntityManager $em): ?User
    {
        /** @var User|null $user */
        $user = null;
        if ($token = $this->tokenStorage->getToken()) {
            if ($token instanceof UsernamePasswordToken && $token->getUser() instanceof User) {
                $user = $em->merge($token->getUser());
            }
        }

        return $user;
    }

    /**
     * Unset unloggable columns.
     */
    protected function prepareChangeSet(EntityManager $em, UnitOfWork $uow, array $changeSet, $entity): void
    {
        foreach ($changeSet as $key => $value) {
            if ($value[0] == $value[1]) {
                unset($changeSet[$key]);
            } else {
                if (is_object($value[0])) {
                    $changeSet[$key][0] = $this->prepareLogMessage($value[0]);
                }
                if (is_object($value[1])) {
                    $changeSet[$key][1] = $this->prepareLogMessage($value[1]);
                }

                if (get_class($entity) === Option::class) {
                    $changeSet = [
                            'option name' => [
                                '',
                                $entity->getName(),
                            ],
                        ] + $changeSet;
                }
            }
        }

        $changeType = EntityLog::EDIT;

        if ($entity instanceof LoggableInterface) {
            foreach ($entity->getLogIgnoredColumns() as $value) {
                unset($changeSet[$value]);
            }

            if (array_key_exists('deletedAt', $changeSet) && $entity instanceof SoftDeleteLoggableInterface) {
                if ($changeSet['deletedAt'][0] === null) {
                    if ($changeSet['deletedAt'][1] === null) {
                        $changeType = EntityLog::DELETE;
                        $changeSet = $entity->getLogDeleteMessage();
                    } else {
                        $changeType = EntityLog::SOFT_DELETE;
                        $changeSet = $entity->getLogArchiveMessage();
                    }
                } elseif ($changeSet['deletedAt'][1] === null) {
                    $changeSet = $entity->getLogRestoreMessage();
                    $changeType = EntityLog::RESTORE;
                }
            }
        } elseif ($entity instanceof UserGroupPermission) {
            if ($moduleName = (PermissionNames::PERMISSION_HUMAN_NAMES[$entity->getModuleName()] ?? null)) {
                $changeSet['permission'][] = $moduleName;
            }
        }

        if (count($changeSet) > 0) {
            $this->log($em, $uow, $entity, $changeType, $changeSet);
        }
    }

    /**
     * Return insert or delete message from entity.
     *
     * @param object $entity
     * @param int    $type
     *
     * @return array|string|null
     */
    protected function getLogMessage($entity, $type)
    {
        if ($entity instanceof LoggableInterface) {
            if ($type === EntityLog::INSERT) {
                return $entity->getLogInsertMessage();
            }
            if ($type === EntityLog::DELETE) {
                return $entity->getLogDeleteMessage();
            }
        }

        return null;
    }

    /**
     * Return update message from entity.
     *
     * @param object $object
     *
     * @return array|string|null
     */
    protected function prepareLogMessage($object)
    {
        if ($object instanceof ParentLoggableInterface) {
            return $object->getLogUpdateMessage();
        }

        if ($object instanceof \DateTime) {
            return $object->format(\DATE_RSS);
        }

        return null;
    }

    /**
     * @return int
     */
    private function getUserType(User $user = null)
    {
        if ($user === null) {
            $type = EntityLog::SYSTEM;
        } else {
            if ($user->getClient()) {
                $type = EntityLog::CLIENT;
            } else {
                $type = EntityLog::ADMIN;
            }
        }

        return $type;
    }
}
