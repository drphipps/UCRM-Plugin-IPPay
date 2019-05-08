<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\TwoFactor;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

/**
 * This subscriber handles brute force protection on 2FA form.
 *
 * After 10 failed attempts, user must wait 1 minute before he is allowed to to enter code again.
 *
 * If he tries again before the 1 minute passes, the reset time slides.
 * That means that the next attempt must be at least 1 minute after previous before authentication is possible again.
 */
class TwoFactorBruteForceSubscriber implements EventSubscriberInterface
{
    private const BRUTE_FORCE_COUNT_LIMIT = 10;
    private const BRUTE_FORCE_RESET = '+1 minute';

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TwoFactorAuthenticationEvents::COMPLETE => 'handleAuthenticationComplete',
            TwoFactorAuthenticationEvents::FAILURE => 'handleAuthenticationFailure',
            TwoFactorAuthenticationEvents::ATTEMPT => 'handleAuthenticationAttempt',
        ];
    }

    public function handleAuthenticationComplete(TwoFactorAuthenticationEvent $event): void
    {
        $user = $event->getToken()->getUser();
        if (! $user instanceof User) {
            return;
        }

        // on success we can safely reset the counter
        $user->setTwoFactorFailureCount(0);
        $user->setTwoFactorFailureResetAt(null);
        $this->entityManager->flush($user);
    }

    public function handleAuthenticationFailure(TwoFactorAuthenticationEvent $event): void
    {
        $user = $event->getToken()->getUser();
        if (! $user instanceof User) {
            return;
        }

        // on failure, we increase the counter and if the count is bigger than limit, we set up the reset timestamp
        // the sliding of reset timestamp is intentional
        $user->setTwoFactorFailureCount($user->getTwoFactorFailureCount() + 1);
        if ($user->getTwoFactorFailureCount() >= self::BRUTE_FORCE_COUNT_LIMIT) {
            $user->setTwoFactorFailureResetAt(new \DateTime(self::BRUTE_FORCE_RESET));
        }
        $this->entityManager->flush($user);
    }

    public function handleAuthenticationAttempt(TwoFactorAuthenticationEvent $event): void
    {
        $user = $event->getToken()->getUser();
        if (! $user instanceof User) {
            return;
        }

        // if the reset timestamp is reached, reset the counter before attempting authentication
        $now = new \DateTime();
        if ($user->getTwoFactorFailureResetAt() && $now >= $user->getTwoFactorFailureResetAt()) {
            $user->setTwoFactorFailureCount(0);
            $user->setTwoFactorFailureResetAt(null);
            $this->entityManager->flush($user);

            return;
        }

        if ($user->getTwoFactorFailureCount() >= self::BRUTE_FORCE_COUNT_LIMIT) {
            throw new CustomUserMessageAuthenticationException(
                'You have exceeded the number of allowed login attempts. Please try again later.'
            );
        }
    }
}
