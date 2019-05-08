<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Entity\EntityLog;
use AppBundle\Entity\User;
use AppBundle\Exception\NoClientContactException;
use AppBundle\Exception\PublicUrlGeneratorException;
use AppBundle\Facade\UserFacade;
use AppBundle\Form\Data\PasswordResetData;
use AppBundle\Form\Data\ResetPasswordData;
use AppBundle\Form\PasswordResetType;
use AppBundle\Form\ResetPasswordType;
use AppBundle\Security\Permission;
use AppBundle\Service\ActionLogger;
use AppBundle\Service\ResetPasswordEmailSender;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/reset-password")
 */
class ResetPasswordController extends BaseController
{
    private const RESET_TOKEN_TTL = 60 * 30; // 30 minutes

    /**
     * @Route("", name="reset_password_index")
     * @Method({"GET", "POST"})
     * @Permission("public")
     */
    public function indexAction(Request $request): Response
    {
        $resetPassword = new ResetPasswordData();
        $form = $this->createForm(ResetPasswordType::class, $resetPassword);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->em->getRepository(User::class)->loadUserByUsername($resetPassword->username);

            if (null === $user || ! $user->isEnabled()) {
                $form->addError(new FormError('No account with this username found.'));

                return $this->render(
                    'user/resetting/reset_password.html.twig',
                    [
                        'form' => $form->createView(),
                    ]
                );
            }

            // anti DDoS
            if ($user->getConfirmationToken() && ! $user->isPasswordRequestExpired(3)) {
                $form->addError(
                    new FormError('You can\'t reset password because you\'ve reached a password reset limit.')
                );

                return $this->render(
                    'user/resetting/reset_password.html.twig',
                    [
                        'form' => $form->createView(),
                    ]
                );
            }

            $this->get(UserFacade::class)->handleRequestPasswordReset($user);

            try {
                $this->container->get(ResetPasswordEmailSender::class)->sendResettingEmailMessage($user);
            } catch (PublicUrlGeneratorException | NoClientContactException $exception) {
                $form->addError(new FormError($exception->getMessage()));

                return $this->render(
                    'user/resetting/reset_password.html.twig',
                    [
                        'form' => $form->createView(),
                    ]
                );
            }

            if ($user->getClient()) {
                $email = $user->getClient()->getContactEmails();
                $email = $email ? reset($email) : '-';
            } else {
                $email = $user->getEmail();
            }

            $message['logMsg'] = [
                'message' => 'User has requested a password reset link. It was sent to %s.',
                'replacements' => $email,
            ];

            $this->get(ActionLogger::class)->log($message, $user, $user->getClient(), EntityLog::PASSWORD_CHANGE);

            return $this->redirectToRoute('reset_password_check_email');
        }

        return $this->render(
            'user/resetting/reset_password.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/do-reset/{confirmationToken}", name="reset_password_do_reset")
     * @Method({"GET", "POST"})
     * @Permission("public")
     */
    public function doResetAction(Request $request, string $confirmationToken): Response
    {
        $user = $this->em->getRepository(User::class)->findOneBy(
            [
                'confirmationToken' => $confirmationToken,
                'deletedAt' => null,
            ]
        );
        if (! $user) {
            throw $this->createNotFoundException();
        }

        if ($user->isPasswordRequestExpired(self::RESET_TOKEN_TTL)) {
            $user->setPasswordRequestedAt(null);
            $user->setConfirmationToken(null);
            $this->get(UserFacade::class)->handleUpdate($user);

            $this->addTranslatedFlash(
                'error',
                'Password reset token expired. Please request the password reset again.'
            );

            return $this->redirectToRoute('reset_password_index');
        }

        $passwordReset = new PasswordResetData();
        $passwordReset->user = $user;
        $form = $this->createForm(PasswordResetType::class, $passwordReset);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(UserFacade::class)->handleResetPassword($user, $passwordReset->password);

            $message['logMsg'] = [
                'message' => 'User changed his password via a reset link.',
                'replacements' => '',
            ];

            $this->get(ActionLogger::class)->log($message, $user, $user->getClient(), EntityLog::PASSWORD_CHANGE);

            $this->addTranslatedFlash('success', 'Your password has been changed, you can now login.');

            return $this->redirectToRoute('app_security_login');
        }

        return $this->render(
            'user/resetting/reset.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/check-email", name="reset_password_check_email")
     * @Permission("public")
     */
    public function checkEmailAction()
    {
        return $this->render('user/resetting/check_email.html.twig');
    }
}
