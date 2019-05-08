<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Repository;

use AppBundle\Entity\User;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

class UserRepository extends BaseRepository implements UserLoaderInterface
{
    /**
     * Find active user to log in by username.
     *
     * @param string $username
     *
     * @throws NonUniqueResultException
     */
    public function loadUserByUsername($username): ?User
    {
        $queryBuilder = $this->createQueryBuilder('u')
            ->select('u, g, p')
            ->leftJoin('u.group', 'g')
            ->leftJoin('g.permissions', 'p')
            ->where('u.deletedAt IS NULL')
            ->andWhere('u.username = :login')
            ->setParameter('login', $username);

        try {
            return $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    public function getWizardUser(): ?User
    {
        return $this->findOneBy(
            [
                'deletedAt' => null,
                'role' => User::ROLE_WIZARD,
            ]
        );
    }

    public function getAllIdentifiers(): array
    {
        $queryBuilder = $this->createQueryBuilder('u')
            ->select('u.username');

        $result = $queryBuilder->getQuery()->getResult();

        return array_filter(array_column($result, 'username'));
    }

    public function getMaxLastLogin(): ?\DateTime
    {
        try {
            $lastLogin = $this->createQueryBuilder('u')
                ->select('MAX(u.lastLogin)')
                ->getQuery()->getSingleScalarResult();
        } catch (NoResultException $exception) {
            return null;
        }

        if (! $lastLogin) {
            return null;
        }

        return new \DateTime($lastLogin, new \DateTimeZone('UTC'));
    }

    public function findAllAdminsForm(): array
    {
        $result = $this->createQueryBuilder('u')
            ->select('u.fullName AS u_full_name')
            ->addSelect('u.id AS u_id')
            ->andWhere('u.role IN (:roles)')
            ->andWhere('u.deletedAt IS NULL')
            ->setParameter('roles', User::ADMIN_ROLES)
            ->addOrderBy('u_full_name')
            ->getQuery()
            ->getResult();

        return array_column($result, 'u_full_name', 'u_id');
    }

    public function findAllAdminsForTimeline(): array
    {
        $result = $this->createQueryBuilder('u')
            ->addSelect('u.avatarColor AS avatar_color')
            ->addSelect('u.id AS id')
            ->andWhere('u.role IN (:roles)')
            ->andWhere('u.deletedAt IS NULL')
            ->andWhere('u.isActive = true')
            ->setParameter('roles', User::ADMIN_ROLES)
            ->addOrderBy('u.fullName')
            ->getQuery()
            ->getResult();

        return array_column($result, null, 'id');
    }

    public function existsAdminWithEnabledTwoFactorAuthentication(): bool
    {
        return (bool) $this->createExistsQueryBuilder()
            ->andWhere('u.googleAuthenticatorSecret IS NOT NULL')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function existsAdminWithEnabledGoogleCalendarSynchronization(): bool
    {
        return (bool) $this->createExistsQueryBuilder()
            ->andWhere('u.googleCalendarId IS NOT NULL')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getAdminCount(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u)')
            ->where('u.role IN (:roles)')
            ->setParameter('roles', User::ADMIN_ROLES)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createExistsQueryBuilder(): QueryBuilder
    {
        $qb = $this->createQueryBuilder('u')
            ->select('1')
            ->andWhere('u.deletedAt IS NULL')
            ->andWhere('u.isActive = TRUE')
            ->setMaxResults(1);

        return $qb;
    }
}
