<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command;

use AppBundle\Entity\User;
use AppBundle\Facade\UserFacade;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DisableTwoFactorAuthenticationCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var UserFacade
     */
    private $userFacade;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger, UserFacade $userFacade)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->userFacade = $userFacade;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:twoFactorAuthentication:disable');
        $this->addArgument(
            'username',
            InputArgument::REQUIRED
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = $input->getArgument('username');

        /** @var User|null $user */
        $user = $this->entityManager->getRepository(User::class)->loadUserByUsername($username);
        if (! $user) {
            $this->logger->error(sprintf('User with username "%s" not found.', $username));

            return 1;
        }

        if ($user->isGoogleAuthenticatorEnabled()) {
            $user->setGoogleAuthenticatorSecret(null);
            $user->setBackupCodes([]);
            $this->userFacade->handleUpdate($user);
        }
        $this->logger->notice(sprintf('Two-factor authentication disabled for user with username "%s".', $username));

        return 0;
    }
}
