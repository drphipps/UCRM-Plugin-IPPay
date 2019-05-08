<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Security;

use AppBundle\DataProvider\LocaleDataProvider;
use AppBundle\Entity\EntityLog;
use AppBundle\Entity\User;
use AppBundle\Facade\UserFacade;
use AppBundle\Service\ActionLogger;
use AppBundle\Service\Locale\LocaleSessionUpdater;
use AppBundle\Util\Helpers;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var ActionLogger
     */
    private $actionLogger;

    /**
     * @var LocaleDataProvider
     */
    private $localeDataProvider;

    /**
     * @var LocaleSessionUpdater
     */
    private $localeSessionUpdater;

    /**
     * @var UserFacade
     */
    private $userFacade;

    public function __construct(
        RouterInterface $router,
        ActionLogger $actionLogger,
        LocaleDataProvider $localeDataProvider,
        LocaleSessionUpdater $localeSessionUpdater,
        UserFacade $userFacade
    ) {
        $this->router = $router;
        $this->actionLogger = $actionLogger;
        $this->localeDataProvider = $localeDataProvider;
        $this->localeSessionUpdater = $localeSessionUpdater;
        $this->userFacade = $userFacade;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        /** @var User $user */
        $user = $token->getUser();

        if ($user->getRole() === User::ROLE_WIZARD) {
            return new RedirectResponse($this->router->generate('wizard_index'));
        }

        $this->userFacade->updateLastLogin($user);

        $message['logMsg'] = [
            'message' => 'Login successful.',
            'replacements' => '',
        ];
        $this->actionLogger->log($message, $user, $user->getClient(), EntityLog::LOGIN);

        if (Helpers::isDemo() && $localeId = $request->request->get('locale')) {
            if (is_numeric($localeId)) {
                $locale = $this->localeDataProvider->getLocaleById((int) $localeId);
                if ($locale) {
                    $user->setLocale($locale);
                    $this->localeSessionUpdater->update($user);
                }
            }
        }

        return new RedirectResponse($this->determineTargetUrl($request, $token));
    }

    protected function determineTargetUrl(Request $request, TokenInterface $token): string
    {
        if ($targetUrl = $request->get('_target_path')) {
            return $targetUrl;
        }

        if ($targetUrl = $request->getSession()->get('_security.main.target_path')) {
            return $targetUrl;
        }

        foreach ($token->getRoles() as $role) {
            if (User::ROLE_CLIENT === $role->getRole()) {
                return $this->router->generate('client_zone_client_index');
            }
        }

        return $this->router->generate('homepage');
    }
}
