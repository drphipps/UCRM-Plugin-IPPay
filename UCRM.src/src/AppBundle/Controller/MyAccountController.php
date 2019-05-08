<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use ApiBundle\Entity\UserAuthenticationKey;
use AppBundle\DataProvider\GoogleCalendarDataProvider;
use AppBundle\DataProvider\MobileAppDataProvider;
use AppBundle\Entity\User;
use AppBundle\Exception\GoogleCalendarException;
use AppBundle\Exception\OAuthException;
use AppBundle\Exception\PublicUrlGeneratorException;
use AppBundle\Facade\GoogleOAuthFacade;
use AppBundle\Facade\UserAuthenticationKeyFacade;
use AppBundle\Facade\UserFacade;
use AppBundle\Facade\UserPersonalizationFacade;
use AppBundle\Form\UserSettings;
use AppBundle\Security\Permission;
use AppBundle\Service\GoogleCalendar\ClientFactory;
use AppBundle\Service\Locale\LocaleSessionUpdater;
use AppBundle\Service\PublicUrlGenerator;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use SchedulingBundle\Security\SchedulingPermissions;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/user/settings/account")
 */
class MyAccountController extends BaseController
{
    /**
     * @Route("", name="user_settings_account")
     * @Method({"GET", "POST"})
     * @Permission("guest")
     */
    public function accountAction(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $googleCalendars = $this->getGoogleCalendars($user);
        $form = $this->createForm(
            UserSettings::class,
            $user,
            [
                'google_calendars' => is_array($googleCalendars) ? array_flip($googleCalendars) : [],
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(UserFacade::class)->handleUpdate($user);
            $this->get(LocaleSessionUpdater::class)->update($user);

            $this->addTranslatedFlash('success', 'User has been saved.');

            return $this->redirectToRoute('user_settings_account');
        }

        return $this->render(
            'user/settings/account.html.twig',
            [
                'form' => $form->createView(),
                'user' => $user,
                'iCalExportUrl' => $this->getICalExportUrl($user),
                'googleCalendars' => $googleCalendars,
                'userAuthenticationKeys' => $this->get(MobileAppDataProvider::class)->getUserAuthenticationKeys($user),
            ]
        );
    }

    /**
     * @Route("/connect-mobile-app", name="user_settings_account_connect_mobile_app")
     * @Method("GET")
     * @Permission("guest")
     */
    public function connectMobileAppAction(): Response
    {
        return $this->render(
            'user/settings/connect_mobile_app.html.twig',
            [
                'qrCode' => $this->get(MobileAppDataProvider::class)->getConnectQrCode($this->getUser()),
            ]
        );
    }

    /**
     * @Route(
     *     "/revoke-authentication-key/{id}",
     *     name="user_settings_account_revoke_authentication_key",
     *     requirements={"id": "\d+"}
     * )
     * @Method("GET")
     * @CsrfToken()
     * @Permission("guest")
     */
    public function revokeAuthenticationKey(UserAuthenticationKey $authenticationKey): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($authenticationKey->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $this->get(UserAuthenticationKeyFacade::class)->handleDelete($authenticationKey);
        $this->addTranslatedFlash('success', 'Authentication key has been revoked.');

        return $this->redirectToRoute('user_settings_account');
    }

    /**
     * @Route("/synchronize-google-calendar", name="user_settings_account_synchronize_google_calendar")
     * @Method("GET")
     * @CsrfToken()
     * @Permission("guest")
     */
    public function synchronizeGoogleCalendarAction(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $user->setNextGoogleCalendarSynchronization(null);
        $this->get(UserFacade::class)->handleUpdate($user);

        $this->addTranslatedFlash('success', 'Google Calendar will be synchronized in several minutes.');

        return $this->redirectToRoute('user_settings_account');
    }

    /**
     * @Route(
     *     "/edit-personalization/{field}",
     *     name="user_settings_account_edit_personalization",
     *     requirements={"field": "[a-zA-Z0-9_]+"}
     * )
     * @Method("GET")
     * @CsrfToken()
     * @Permission("guest")
     */
    public function editPersonalizationAction(Request $request, string $field): Response
    {
        $value = $request->get('value');
        /** @var User $user */
        $user = $this->getUser();
        $userPersonalization = $user->getUserPersonalization();
        try {
            $this->get('property_accessor')->setValue($userPersonalization, $field, $value);
        } catch (\Throwable $e) {
            throw $this->createAccessDeniedException();
        }

        $this->get(UserPersonalizationFacade::class)->handleEdit($userPersonalization);

        return new Response();
    }

    private function getGoogleCalendars(User $user): ?array
    {
        $calendars = [];
        if ($user->getGoogleOAuthToken()) {
            try {
                $this->get(GoogleOAuthFacade::class)->refreshTokenIfExpired($user);
                $googleClient = $this->get(ClientFactory::class)->create($user->getGoogleOAuthToken());
                $calendars = $this->get(GoogleCalendarDataProvider::class)->getWritableCalendars($googleClient);
            } catch (OAuthException | GoogleCalendarException $exception) {
                // In case of unauthorized token show message about it, otherwise ignore.
                if ($exception->getCode() === 401) {
                    return null;
                }
            }
        }

        return $calendars;
    }

    private function getICalExportUrl(User $user): ?string
    {
        $url = null;
        if ($this->isGranted(Permission::VIEW, SchedulingPermissions::JOBS_MY)) {
            try {
                $url = $this->get(PublicUrlGenerator::class)->generate(
                    'scheduling_calendar_export_ical',
                    [
                        'id' => $user->getId(),
                    ]
                );
            } catch (PublicUrlGeneratorException $exception) {
                $this->addTranslatedFlash('error', $exception->getMessage());
            }
        }

        return $url;
    }
}
