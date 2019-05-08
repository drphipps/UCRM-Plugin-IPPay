<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Security;

use ApiBundle\Entity\UserAuthenticationKey;
use AppBundle\Entity\AppKey;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Utils\Strings;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class ApiUserProvider implements UserProviderInterface
{
    public const USER_KEY_PREFIX = 'user-key-';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function loadUserByUsername($key): UserInterface
    {
        if (Strings::startsWith($key, self::USER_KEY_PREFIX)) {
            return $this->loadByUserKey(Strings::substring($key, Strings::length(self::USER_KEY_PREFIX)));
        }

        return $this->loadByAppKey($key);
    }

    private function loadByUserKey($key): UserInterface
    {
        $authenticationKey = $this->entityManager
            ->getRepository(UserAuthenticationKey::class)
            ->findOneBy(
                [
                    'key' => $key,
                ]
            );

        if (! $authenticationKey) {
            throw new UsernameNotFoundException(sprintf('User with key "%s" not found.', $key));
        }

        if ($authenticationKey->isExpired()) {
            $this->entityManager->remove($authenticationKey);
            $this->entityManager->flush();
            throw new UsernameNotFoundException(sprintf('User with key "%s" not found.', $key));
        }

        $authenticationKey->setLastUsedDate(new \DateTime());
        $this->entityManager->flush();

        return $authenticationKey->getUser();
    }

    private function loadByAppKey($key): UserInterface
    {
        $appKey = $this->entityManager
            ->getRepository(AppKey::class)
            ->findOneBy(
                [
                    'key' => $key,
                    'deletedAt' => null,
                ]
            );

        if (! $appKey) {
            throw new UsernameNotFoundException('App key not found.');
        }

        $appKey->setLastUsedDate(new \DateTime());
        $this->entityManager->flush();

        return new AppKeyUser($appKey);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        throw new UnsupportedUserException('Refresh not supported in API, you should always send auth token.');
    }

    public function supportsClass($class): bool
    {
        return $class === User::class;
    }
}
