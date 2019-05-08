<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\HeaderNotification;

use AppBundle\Component\HeaderNotification\DataProvider\HeaderNotificationsDataProvider;
use AppBundle\Component\HeaderNotification\Query\CreateHeaderNotificationQuery;
use AppBundle\Component\HeaderNotification\Query\QueryInterface;
use AppBundle\Component\HeaderNotification\Query\SendByPermissionQuery;
use AppBundle\Component\HeaderNotification\Query\SendToAdminQuery;
use AppBundle\Component\HeaderNotification\Query\SendToAllAdminsQuery;
use AppBundle\Entity\HeaderNotification;
use AppBundle\Entity\User;
use AppBundle\Service\SocketEvent\SocketEvent;
use AppBundle\Service\SocketEvent\SocketEventException;
use AppBundle\Service\SocketEvent\SocketEventSender;
use Doctrine\DBAL\Connection;

class HeaderNotificationSender
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var SocketEventSender
     */
    private $socketEventSender;

    /**
     * @var HeaderNotificationsDataProvider
     */
    private $headerNotificationsDataProvider;

    public function __construct(
        Connection $connection,
        SocketEventSender $socketEventSender,
        HeaderNotificationsDataProvider $headerNotificationsDataProvider
    ) {
        $this->connection = $connection;
        $this->socketEventSender = $socketEventSender;
        $this->headerNotificationsDataProvider = $headerNotificationsDataProvider;
    }

    public function sendToAllAdmins(HeaderNotification $notification): void
    {
        $this->executeQueries(
            $this->createHeaderNotificationQuery($notification),
            new SendToAllAdminsQuery($notification->getId())
        );

        $this->sendNewHeaderNotificationSocketEvent(
            $notification,
            function (SocketEvent $event) {
                $event->setRoles(User::ADMIN_ROLES);
            }
        );
    }

    public function sendToAdmin(HeaderNotification $notification, User $user): void
    {
        $this->executeQueries(
            $this->createHeaderNotificationQuery($notification),
            new SendToAdminQuery($notification->getId(), $user->getId())
        );

        $this->sendNewHeaderNotificationSocketEvent(
            $notification,
            function (SocketEvent $event) use ($user) {
                $event->addUserId($user->getId());
            }
        );
    }

    public function sendByPermission(HeaderNotification $notification, string $permissionName): void
    {
        $this->executeQueries(
            $this->createHeaderNotificationQuery($notification),
            new SendByPermissionQuery($notification->getId(), $permissionName)
        );

        $this->sendNewHeaderNotificationSocketEvent(
            $notification,
            function (SocketEvent $event) use ($notification) {
                $event->setUserIds(
                    $this->headerNotificationsDataProvider->getHeaderNotificationUserIds($notification->getId())
                );
            }
        );
    }

    private function executeQueries(QueryInterface ...$queries): void
    {
        $this->connection->transactional(
            function () use ($queries) {
                foreach ($queries as $query) {
                    $this->connection->executeQuery(
                        $query->getQuery(),
                        $query->getParameters(),
                        $query->getParameterTypes()
                    );
                }
            }
        );
    }

    private function createHeaderNotificationQuery(HeaderNotification $notification): CreateHeaderNotificationQuery
    {
        return new CreateHeaderNotificationQuery(
            $notification->getId(),
            $notification->getType(),
            $notification->getTitle(),
            $notification->getDescription(),
            (clone $notification->getCreatedDate())
                ->setTimezone(new \DateTimeZone('UTC'))
                ->format($this->connection->getDatabasePlatform()->getDateTimeFormatString()),
            $notification->getLink(),
            $notification->isLinkTargetBlank()
        );
    }

    private function sendNewHeaderNotificationSocketEvent(HeaderNotification $notification, callable $beforeSend): void
    {
        try {
            $socketEvent = new SocketEvent(SocketEvent::EVENT_NEW_HEADER_NOTIFICATION);
            $socketEvent->setData(
                [
                    'timestamp' => $notification->getCreatedDate()->getTimestamp(),
                ]
            );

            $beforeSend($socketEvent);

            $this->socketEventSender->send($socketEvent);
        } catch (SocketEventException $exception) {
            // not important, silently continue
        }
    }
}
