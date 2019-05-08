<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Entity\EntityLog;
use AppBundle\Facade\UserFacade;
use AppBundle\Form\ChangePasswordType;
use AppBundle\Form\Data\ChangePasswordData;
use AppBundle\Security\Permission;
use AppBundle\Service\ActionLogger;
use AppBundle\Util\Helpers;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/security/users")
 */
class PasswordController extends BaseController
{
    /**
     * @Route("/change-password", name="user_change_password")
     * @Method({"GET", "POST"})
     * @Permission("guest")
     */
    public function changePasswordAction(Request $request): Response
    {
        // Any logged in user can change his own password
        if (! $this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();
        $this->notDeleted($user);

        $changePasswordData = new ChangePasswordData();
        $changePasswordData->user = $user;
        $url = $this->generateUrl('user_change_password');
        $form = $this->createForm(ChangePasswordType::class, $changePasswordData, ['action' => $url]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (! Helpers::isDemo()) {
                $this->get(UserFacade::class)->handleChangePassword($user, $changePasswordData->newPassword);
            }

            $message['logMsg'] = [
                'message' => 'User changed his password.',
                'replacements' => '',
            ];

            $this->get(ActionLogger::class)->log($message, $user, $user->getClient(), EntityLog::PASSWORD_CHANGE);

            return new JsonResponse(
                [
                    'status' => 1,
                    'message' => 'Password has been changed.',
                ]
            );
        }

        return $this->render(
            'user/change_password.html.twig',
            [
                'user' => $user,
                'form' => $form->createView(),
            ]
        );
    }
}
