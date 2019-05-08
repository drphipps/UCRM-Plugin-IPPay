<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Facade\UserFacade;
use AppBundle\Form\Data\VerifyTwoFactorAuthenticationData;
use AppBundle\Form\Data\VerifyTwoFactorDisableData;
use AppBundle\Form\VerifyTwoFactorAuthenticationType;
use AppBundle\Form\VerifyTwoFactorDisableType;
use AppBundle\Security\Permission;
use AppBundle\Security\TwoFactor\GoogleAuthenticator;
use AppBundle\Service\DownloadResponseFactory;
use AppBundle\Util\Helpers;
use Endroid\QrCode\Factory\QrCodeFactory;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * @Route("/user/settings/account/two-factor-authentication")
 */
class TwoFactorAuthenticationController extends BaseController
{
    /**
     * @Route(
     *     "/enable",
     *     name="two_factor_authentication_enable",
     * )
     * @Method("GET")
     * @CsrfToken()
     * @Permission("guest")
     */
    public function enableAction(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($user->getGoogleAuthenticatorSecret()) {
            $this->addTranslatedFlash('error', 'Two-factor authentication is already enabled.');

            return $this->redirectToRoute('user_settings_account');
        }

        $this->get(Session::class)->set(
            GoogleAuthenticator::GOOGLE_AUTHENTICATOR_SECRET_SESSION_KEY . $user->getId(),
            $this->get(GoogleAuthenticator::class)->generateSecret()
        );

        return $this->redirectToRoute('two_factor_authentication_verify');
    }

    /**
     * @Route(
     *     "/verify",
     *     name="two_factor_authentication_verify",
     * )
     * @Method({"GET", "POST"})
     * @Permission("guest")
     */
    public function verifyAction(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($user->getGoogleAuthenticatorSecret()) {
            $this->addTranslatedFlash('error', 'Two-factor authentication is already enabled.');

            return $this->redirectToRoute('user_settings_account');
        }

        $session = $this->get(Session::class);
        $secret = $session->get(GoogleAuthenticator::GOOGLE_AUTHENTICATOR_SECRET_SESSION_KEY . $user->getId());
        if (! $secret) {
            $this->addTranslatedFlash('error', 'There is no code to verify.');

            return $this->redirectToRoute('user_settings_account');
        }

        $googleAuthenticator = $this->get(GoogleAuthenticator::class);
        $user->setGoogleAuthenticatorSecret($secret);

        $data = new VerifyTwoFactorAuthenticationData();
        $form = $this->createForm(VerifyTwoFactorAuthenticationType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (! $googleAuthenticator->checkCode($user, $data->code)) {
                $form->get('code')->addError(new FormError('Invalid two-factor authentication code.'));
            }

            if ($form->isValid()) {
                $session->remove(GoogleAuthenticator::GOOGLE_AUTHENTICATOR_SECRET_SESSION_KEY . $user->getId());
                if (! Helpers::isDemo()) {
                    $this->get(UserFacade::class)->handleUpdate($user);
                }

                $this->addTranslatedFlash('success', 'Two-factor authentication has been enabled.');

                return $this->redirectToRoute('user_settings_account');
            }
        }

        $qrContent = $googleAuthenticator->getQRContent($user);
        $qrCode = $this->get(QrCodeFactory::class)->create($qrContent)->writeDataUri();

        return $this->render(
            'user/two_factor/enable.html.twig',
            [
                'user' => $user,
                'qrCode' => $qrCode,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route(
     *     "/fallback",
     *     name="two_factor_authentication_fallback",
     * )
     * @Method("GET")
     * @Permission("guest")
     */
    public function fallbackAction(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($user->getGoogleAuthenticatorSecret()) {
            throw $this->createNotFoundException();
        }

        $secret = $this->get(Session::class)->get(
            GoogleAuthenticator::GOOGLE_AUTHENTICATOR_SECRET_SESSION_KEY . $user->getId()
        );
        if (! $secret) {
            throw $this->createNotFoundException();
        }

        return $this->render(
            'user/two_factor/fallback.html.twig',
            [
                'secret' => $secret,
            ]
        );
    }

    /**
     * @Route(
     *     "/disable",
     *     name="two_factor_authentication_disable",
     * )
     * @Method({"GET", "POST"})
     * @Permission("guest")
     */
    public function disableAction(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (! $user->getGoogleAuthenticatorSecret()) {
            $this->addTranslatedFlash('error', 'Two-factor authentication is not enabled on your account.');

            return $this->redirectToRoute('user_settings_account');
        }

        $data = new VerifyTwoFactorDisableData();
        $form = $this->createForm(VerifyTwoFactorDisableType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (! Helpers::isDemo()) {
                $user->setGoogleAuthenticatorSecret(null);
                $user->setBackupCodes([]);
                $this->get(UserFacade::class)->handleUpdate($user);
            }

            $this->addTranslatedFlash('success', 'Two-factor authentication has been disabled.');

            return $this->redirectToRoute('user_settings_account');
        }

        return $this->render(
            'user/two_factor/disable.html.twig',
            [
                'user' => $user,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route(
     *     "/backup-codes",
     *     name="two_factor_authentication_backup_codes",
     * )
     * @Method("GET")
     * @Permission("guest")
     */
    public function backupCodesAction(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (! $user->getGoogleAuthenticatorSecret()) {
            $this->addTranslatedFlash('error', 'Two-factor authentication is not enabled on your account.');

            return $this->redirectToRoute('user_settings_account');
        }

        return $this->render(
            'user/two_factor/backup_codes.html.twig',
            [
                'user' => $user,
                'backupCodesText' => implode("\r\n", array_keys($user->getBackupCodes())),
            ]
        );
    }

    /**
     * @Route(
     *     "/generate-backup-codes",
     *     name="two_factor_authentication_generate_backup_codes",
     * )
     * @Method("GET")
     * @CsrfToken()
     * @Permission("guest")
     */
    public function generateBackupCodesAction(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (! $user->getGoogleAuthenticatorSecret()) {
            $this->addTranslatedFlash('error', 'Two-factor authentication is not enabled on your account.');

            return $this->redirectToRoute('user_settings_account');
        }

        $this->get(UserFacade::class)->generateBackupCodes($user);
        $this->addTranslatedFlash('success', 'Backup codes were generated.');

        return $this->redirectToRoute('two_factor_authentication_backup_codes');
    }

    /**
     * @Route(
     *     "/download-backup-codes",
     *     name="two_factor_authentication_download_backup_codes",
     * )
     * @Method("GET")
     * @Permission("guest")
     */
    public function downloadBackupCodesAction(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (! $user->getGoogleAuthenticatorSecret()) {
            $this->addTranslatedFlash('error', 'Two-factor authentication is not enabled on your account.');

            return $this->redirectToRoute('user_settings_account');
        }

        if (! $user->getBackupCodes()) {
            $this->addTranslatedFlash('error', 'There are no backup codes on your account.');

            return $this->redirectToRoute('user_settings_account');
        }

        return $this->get(DownloadResponseFactory::class)->createFromContent(
            implode("\r\n", array_keys($user->getBackupCodes())),
            'ucrm_backup_codes.txt',
            null,
            'text/plain'
        );
    }
}
