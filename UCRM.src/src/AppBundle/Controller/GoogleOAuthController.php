<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Exception\OAuthException;
use AppBundle\Facade\GoogleOAuthFacade;
use AppBundle\Security\Permission;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * @Route("/oauth/google")
 */
class GoogleOAuthController extends BaseController
{
    /**
     * @Route("/authorize", name="google_oauth_authorize")
     * @Method("GET")
     * @CsrfToken()
     * @Permission("guest")
     */
    public function authorizeAction(Request $request): Response
    {
        $state = $request->get('state');

        try {
            $authUrl = $this->get(GoogleOAuthFacade::class)->createAuthUrl($state);
        } catch (OAuthException | \InvalidArgumentException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());

            return $this->fallbackRedirect($state);
        }

        return new RedirectResponse($authUrl);
    }

    /**
     * @Route("/callback", name="google_oauth_callback")
     * @Method("GET")
     * @Permission("public")
     */
    public function callbackAction(Request $request): Response
    {
        $accessCode = $request->get('code');
        if (! $accessCode) {
            $this->addTranslatedFlash('error', 'Request for Google access was not successful.');

            return $this->fallbackRedirect($request->get('state'));
        }

        /** @var User $user */
        $user = $this->getUser();

        try {
            $this->get(GoogleOAuthFacade::class)->fetchAccessToken($user, $accessCode);
        } catch (OAuthException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());

            return $this->fallbackRedirect($request->get('state'));
        }

        $this->addTranslatedFlash('success', 'Google access successfully created.');

        return $this->fallbackRedirect($request->get('state'));
    }

    /**
     * @Route("/revoke", name="google_oauth_revoke")
     * @Method("GET")
     * @CsrfToken()
     * @Permission("guest")
     */
    public function revokeAction(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $this->get(GoogleOAuthFacade::class)->revokeToken($user);

            $this->addTranslatedFlash('success', 'Google access successfully revoked.');
        } catch (OAuthException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());
        }

        return $this->fallbackRedirect($request->get('state'));
    }

    private function fallbackRedirect(?string $route): RedirectResponse
    {
        try {
            return $this->redirectToRoute($route);
        } catch (RouteNotFoundException $exception) {
            return $this->redirectToRoute('homepage');
        }
    }
}
