<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Network;

use AppBundle\Entity\Device;
use AppBundle\Entity\Option;
use AppBundle\Entity\ServiceDevice;
use AppBundle\Entity\User;
use AppBundle\Event\User\UserArchiveEvent;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManager;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class ClearPingNotificationUserWhenUserIsArchivedSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var User[]
     */
    private $users = [];

    /**
     * @var Options
     */
    private $options;

    public function __construct(
        EntityManager $entityManager,
        Options $options
    ) {
        $this->entityManager = $entityManager;
        $this->options = $options;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserArchiveEvent::class => 'handleUserArchiveEvent',
        ];
    }

    public function handleUserArchiveEvent(UserArchiveEvent $event): void
    {
        if ($event->getUser()->isAdmin()) {
            $this->users[] = $event->getUser();
        }
    }

    public function preFlush(): void
    {
        if (! $this->users) {
            return;
        }

        $notificationPingUser = $this->entityManager->getRepository(Option::class)->findOneBy(
            [
                'code' => Option::NOTIFICATION_PING_USER,
            ]
        );

        if ($notificationPingUser) {
            foreach ($this->users as $user) {
                if ((int) $notificationPingUser->getValue() === $user->getId()) {
                    $notificationPingUser->setValue(null);
                    break;
                }
            }
        }

        $this->entityManager
            ->createQueryBuilder()
            ->update(Device::class, 'd')
            ->set('d.pingNotificationUser', ':user')
            ->where('d.pingNotificationUser IN (:users)')
            ->setParameter('user', null)
            ->setParameter('users', $this->users)
            ->getQuery()
            ->execute();

        $this->entityManager
            ->createQueryBuilder()
            ->update(ServiceDevice::class, 'sd')
            ->set('sd.pingNotificationUser', ':user')
            ->where('sd.pingNotificationUser IN (:users)')
            ->setParameter('user', null)
            ->setParameter('users', $this->users)
            ->getQuery()
            ->execute();

        $this->users = [];
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
        $this->options->refresh();
    }

    public function rollback(): void
    {
        $this->users = [];
    }
}
