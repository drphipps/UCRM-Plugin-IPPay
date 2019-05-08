<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Form\Data\FirstLoginData;
use AppBundle\Form\FirstLoginType;
use AppBundle\Security\Permission;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * First login controller.
 *
 * @Route("/first-login")
 */
class FirstLoginController extends BaseController
{
    /**
     * Request reset user password: show form.
     *
     * @Route("/{id}/{firstLoginToken}", name="first_login_index", requirements={"id": "\d+"})
     * @Permission("public")
     */
    public function indexAction(Request $request, User $user, string  $firstLoginToken): Response
    {
        if (! $user || ! $user->canDoFirstLogin() || $user->getFirstLoginToken() !== $firstLoginToken) {
            throw $this->createAccessDeniedException();
        }

        $firstLogin = new FirstLoginData();
        $form = $this->createForm(FirstLoginType::class, $firstLogin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // set user password and activate
            $password = $this->get('security.password_encoder')->encodePassword($user, $firstLogin->password);
            $user->setPassword($password);
            $user->setIsActive(true);
            $user->setFirstLoginToken(null);
            $user->setLastLogin(new \DateTime());

            $this->em->persist($user);
            $this->em->flush();

            // login user
            $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
            $this->get('security.token_storage')->setToken($token);

            return $this->redirectToRoute('client_zone_client_index');
        }

        return $this->render(
            'security/first_login.html.twig',
            [
                'form' => $form->createView(),
                'user' => $user,
            ]
        );
    }
}
