<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\HeaderNotification\DataProvider;

use AppBundle\Entity\HeaderNotificationStatus;
use AppBundle\Entity\User;
use AppBundle\Util\Helpers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class HeaderNotificationsDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage)
    {
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
    }

    public function getLastUnreadTimestamp(): array
    {
        $token = $this->tokenStorage->getToken();
        $user = $token ? $token->getUser() : null;

        if (! $user instanceof User) {
            throw new \RuntimeException('Only possible when a User is logged in.');
        }

        return [
            'lastTimestamp' => $this->entityManager->getRepository(HeaderNotificationStatus::class)
                ->getLastUnreadTimestamp($user) ?: '',
            'userId' => $user->getId(),
        ];
    }

    /**
     * @return int[]
     */
    public function getHeaderNotificationUserIds(string $notificationId): array
    {
        $result = $this->entityManager->getConnection()->fetchAll(
            'SELECT user_id FROM header_notification_status WHERE header_notification_id = ?',
            [
                $notificationId,
            ]
        );

        return Helpers::typeCastAll('int', array_column($result, 'user_id'));
    }
}
