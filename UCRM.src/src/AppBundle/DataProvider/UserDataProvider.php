<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Database\UtcDateTimeType;
use AppBundle\Entity\User;
use AppBundle\Entity\UserGroupPermission;
use AppBundle\Security\Permission;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use SchedulingBundle\Security\SchedulingPermissions;

class UserDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getSuperAdmin(): ?User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(
            [
                'role' => User::ROLE_SUPER_ADMIN,
            ]
        );
    }

    /**
     * @return User[]
     */
    public function getAllAdmins(): array
    {
        return $this->entityManager->getRepository(User::class)->findBy(
            [
                'deletedAt' => null,
                'role' => [
                    User::ROLE_ADMIN,
                    User::ROLE_SUPER_ADMIN,
                ],
            ],
            [
                'id' => 'ASC',
            ]
        );
    }

    /**
     * @return User[]
     */
    public function getUsersForGoogleCalendarSynchronization(): array
    {
        $users = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->andWhere('u.googleCalendarId IS NOT NULL')
            ->andWhere('u.googleOAuthToken IS NOT NULL')
            ->andWhere('u.group IS NOT NULL')
            ->andWhere(
                'u.nextGoogleCalendarSynchronization IS NULL OR u.nextGoogleCalendarSynchronization <= :dateTime'
            )
            ->setParameter('dateTime', new \DateTime(), UtcDateTimeType::NAME)->getQuery()->getResult();

        return array_filter(
            $users,
            function (User $user) {
                if ($user->getRole() === User::ROLE_SUPER_ADMIN) {
                    return true;
                }

                $myJobsPermissions = $user->getGroup()->getPermissions()->filter(
                    function (UserGroupPermission $permission) {
                        return in_array(
                            $permission->getModuleName(),
                            [
                                SchedulingPermissions::JOBS_MY,
                                SchedulingPermissions::JOBS_ALL,
                            ],
                            true
                        );
                    }
                )->toArray();

                /** @var UserGroupPermission $permission */
                foreach ($myJobsPermissions as $permission) {
                    if ($permission->getPermission() !== Permission::DENIED) {
                        return true;
                    }
                }

                return false;
            }
        );
    }

    public function getGridModel(): QueryBuilder
    {
        return $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->join('u.group', 'g')
            ->where('u.deletedAt IS NULL')
            ->andWhere('u.role IN (:roles)')
            ->setParameter('roles', User::ADMIN_ROLES)
            ->groupBy('u.id, g.id');
    }
}
