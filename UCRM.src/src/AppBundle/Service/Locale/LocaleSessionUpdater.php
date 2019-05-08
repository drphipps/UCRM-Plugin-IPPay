<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Locale;

use AppBundle\Entity\User;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class LocaleSessionUpdater
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(SessionInterface $session, TokenStorageInterface $tokenStorage)
    {
        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
    }

    public function update(User $user): void
    {
        $token = $this->tokenStorage->getToken();
        /** @var User|null $authUser */
        $authUser = $token->getUser();
        if ($token && $authUser && $authUser->getId() === $user->getId()) {
            if ($user->getLocale()) {
                $this->session->set('_locale', $user->getLocale()->getCode());
            } else {
                $this->session->remove('_locale');
            }
        }
    }
}
