<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Security;

use AppBundle\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(RouterInterface $router, TokenStorageInterface $tokenStorage)
    {
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Handles an access denied failure.
     */
    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        // If the logged in user is in ROLE_WIZARD redirect him to wizard instead of showing 403 page.
        // Note that the exact role stored on User entity must be checked to correctly determine if
        // the user already went through wizard or not. If he did, the 403 page should be displayed.
        if ($token = $this->tokenStorage->getToken()) {
            /** @var User|null $user */
            $user = $token->getUser();
            if ($user && $user->getRole() === User::ROLE_WIZARD) {
                return new RedirectResponse($this->router->generate('wizard_index'));
            }
        }

        return null;
    }
}
