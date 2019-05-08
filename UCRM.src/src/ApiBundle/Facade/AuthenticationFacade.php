<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Facade;

use ApiBundle\Entity\UserAuthenticationKey;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class AuthenticationFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @throws AuthenticationException
     */
    public function createKeyForUser(
        string $username,
        string $password,
        int $expiration,
        bool $sliding,
        ?string $deviceName
    ): ?UserAuthenticationKey {
        $user = $this->entityManager->getRepository(User::class)->loadUserByUsername($username);

        if (! $user) {
            throw new UsernameNotFoundException();
        }

        if (! in_array($user->getRole(), [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN], true)) {
            throw new UnsupportedUserException();
        }

        if (! $this->passwordEncoder->isPasswordValid($user, $password)) {
            throw new BadCredentialsException();
        }

        $authenticationKey = new UserAuthenticationKey();
        $authenticationKey->setUser($user);
        $authenticationKey->setCreatedDate(new \DateTime());
        $authenticationKey->setKey(base64_encode(random_bytes(48)));
        $authenticationKey->setDeviceName($deviceName);

        // Ensure that key is unique
        do {
            $authenticationKey->setKey(base64_encode(random_bytes(48)));
        } while (
            $this->entityManager->getRepository(UserAuthenticationKey::class)->findOneBy(
                [
                    'key' => $authenticationKey->getKey(),
                ]
            )
        );

        $authenticationKey->setExpiration($expiration);
        $authenticationKey->setSliding($sliding);

        $this->entityManager->persist($authenticationKey);
        $this->entityManager->flush();

        return $authenticationKey;
    }

    public function removeKey(string $key): void
    {
        $authenticationKey = $this->entityManager
            ->getRepository(UserAuthenticationKey::class)
            ->findOneBy(['key' => $key]);

        if (! $authenticationKey) {
            return;
        }

        $this->entityManager->remove($authenticationKey);
        $this->entityManager->flush();
    }
}
