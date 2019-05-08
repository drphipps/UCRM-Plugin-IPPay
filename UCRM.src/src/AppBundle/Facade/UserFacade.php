<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\User;
use AppBundle\Event\User\ResetPasswordEvent;
use AppBundle\Event\User\UserArchiveEvent;
use AppBundle\Util\Helpers;
use Doctrine\ORM\EntityManager;
use Nette\Utils\Random;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use TransactionEventsBundle\TransactionDispatcher;

class UserFacade
{
    private const BACKUP_CODES_COUNT = 10;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $securityPasswordEncoder;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    public function __construct(
        UserPasswordEncoderInterface $securityPasswordEncoder,
        EntityManager $em,
        UserPasswordEncoderInterface $passwordEncoder,
        TransactionDispatcher $transactionDispatcher
    ) {
        $this->securityPasswordEncoder = $securityPasswordEncoder;
        $this->em = $em;
        $this->passwordEncoder = $passwordEncoder;
        $this->transactionDispatcher = $transactionDispatcher;
    }

    public function handleCreate(User $user): void
    {
        $this->em->persist($user);
        $this->em->flush();
    }

    public function handleUpdate(User $user): void
    {
        if ($user->getPlainPassword() !== null) {
            $password = $this->passwordEncoder->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($password);
            $user->setPasswordRequestedAt(null);
            $user->setConfirmationToken(null);
        }

        if (Helpers::isDemo()) {
            return;
        }

        $this->em->flush();
    }

    public function handleRequestPasswordReset(User $user): void
    {
        $user->setConfirmationToken(md5(random_bytes(10)));
        $user->setPasswordRequestedAt(new \DateTime());

        $this->handleUpdate($user);
    }

    public function handleUserDelete(User $user): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($user) {
                $user->setDeletedAt(new \DateTime());
                yield new UserArchiveEvent($user);
            }
        );
    }

    public function handleChangePassword(User $user, string $newPassword): void
    {
        $password = $this->securityPasswordEncoder->encodePassword($user, $newPassword);

        $user->setPassword($password);

        $this->handleUpdate($user);
    }

    public function handleResetPassword(User $user, string $newPassword): void
    {
        $user->setPlainPassword($newPassword);
        $this->handleUpdate($user);

        if ($user->isAdmin()) {
            $this->transactionDispatcher->transactional(
                function () use ($user) {
                    yield new ResetPasswordEvent($user);
                }
            );
        }
    }

    public function updateLastLogin(User $user): void
    {
        $user->setLastLogin(new \DateTime());
        $this->em->flush($user);
    }

    public function generateBackupCodes(User $user): void
    {
        $backupCodes = [];
        for ($i = 0; $i < self::BACKUP_CODES_COUNT; ++$i) {
            do {
                $code = sprintf(
                    '%s-%s',
                    Random::generate(5, '0-9a-f'),
                    Random::generate(5, '0-9a-f')
                );
            } while (array_key_exists($code, $backupCodes));

            $backupCodes[$code] = true;
        }
        $user->setBackupCodes($backupCodes);
        $this->handleUpdate($user);
    }
}
