<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Controller;

use AppBundle\DataProvider\LocaleDataProvider;
use AppBundle\Entity\EntityLog;
use AppBundle\Entity\User;
use AppBundle\Facade\UserFacade;
use AppBundle\Security\Permission;
use AppBundle\Service\ActionLogger;
use AppBundle\Util\Helpers;
use Firebase\JWT\JWT;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;

class SecurityController extends BaseController
{
    /**
     * @Route("/login")
     *
     * @return RedirectResponse|Response
     * @Permission("public")
     */
    public function loginAction(Request $request)
    {
        $wizard = $this->em->getRepository(User::class)->getWizardUser();
        if ($wizard && $wizard->isDefaultWizard()) {
            $token = new UsernamePasswordToken($wizard, null, 'main', $wizard->getRoles());
            $this->get('security.token_storage')->setToken($token);
            $this->get('request_stack')->getCurrentRequest()->getSession()->invalidate();

            return $this->redirectToRoute('wizard_index');
        }

        if (
            $this->get('security.authorization_checker')->isGranted(AuthenticatedVoter::IS_AUTHENTICATED_REMEMBERED)
            && $user = $this->getUser()
        ) {
            if ($user->getRole() === User::ROLE_CLIENT) {
                return $this->redirectToRoute('client_zone_client_index');
            }
            if (in_array($user->getRole(), User::ADMIN_ROLES, true)) {
                return $this->redirectToRoute('homepage');
            }
        }

        $authenticationUtils = $this->get('security.authentication_utils');
        $errorMessage = $authenticationUtils->getLastAuthenticationError();

        $template = $request->isXmlHttpRequest() ? 'security/login_modal.html.twig' : 'security/login.html.twig';

        if ($errorMessage) {
            $message['logMsg'] = [
                'message' => 'Login with username %s failed.',
                'replacements' => $authenticationUtils->getLastUsername(),
            ];
            $logger = $this->container->get(ActionLogger::class);
            $logger->log($message, null, null, EntityLog::LOGIN);
        }

        $localeDataProvider = $this->get(LocaleDataProvider::class);
        $locales = $localeDataProvider->getAllLocales();
        $defaultLocale = $localeDataProvider->getPreferredLocale($request, $locales);

        return $this->render(
            $template,
            [
                'lastUsername' => $authenticationUtils->getLastUsername(),
                'locales' => $locales,
                'defaultLocale' => $defaultLocale,
                'error' => $errorMessage ? $errorMessage->getMessage() : null,
                'isDemo' => Helpers::isDemo(),
                'isWizard' => (bool) $wizard,
                'targetPath' => $request->get('_target_path'),
            ]
        );
    }

    /**
     * @Route("/reset-password", name="app_security_reset_password")
     * @codeCoverageIgnore
     * @Permission("public")
     */
    public function resetPasswordAction()
    {
        // this controller will not be executed, as the route is handled by the Security system
    }

    /**
     * @Route("/login-check", name="login_check")
     * @codeCoverageIgnore
     * @Permission("public")
     */
    public function loginCheckAction()
    {
        return $this->redirect('login');
    }

    /**
     * @Route("/get-jwt", name="app_security_get_jwt", options={"expose":true})
     * @Permission("public")
     */
    public function getJwtAction(): JsonResponse
    {
        if (! $this->get('security.authorization_checker')->isGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();
        $jwt = JWT::encode(
            [
                'userId' => $user->getId(),
                'userGroupId' => $user->getGroup() ? $user->getGroup()->getId() : null,
                'roles' => $user->getRoles(),
                'exp' => time() + 600, // 10 minutes expiration
            ],
            $this->getParameter('secret'),
            'HS256'
        );
        $this->get(UserFacade::class)->updateLastLogin($user);

        return new JsonResponse(
            [
                'token' => $jwt,
            ]
        );
    }
}
