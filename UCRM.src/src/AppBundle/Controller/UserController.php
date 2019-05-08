<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Entity\Option;
use AppBundle\Entity\User;
use AppBundle\Facade\UserFacade;
use AppBundle\Form\UserType;
use AppBundle\Grid\User\UserGridFactory;
use AppBundle\Security\Permission;
use AppBundle\Service\Locale\LocaleSessionUpdater;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/security/users")
 */
class UserController extends BaseController
{
    /**
     * @Route("", name="user_index")
     * @Method("GET")
     * @Permission("view")
     * @Searchable(heading="Users", path="System -> Users")
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(UserGridFactory::class)->create();
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'user/index.html.twig',
            [
                'grid' => $grid,
            ]
        );
    }

    /**
     * @Route("/new", name="user_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request): Response
    {
        $user = new User();
        $user->setRole(User::ROLE_ADMIN);
        $isTicketingEnabled = $this->getOption(Option::TICKETING_ENABLED);
        $form = $this->createForm(UserType::class, $user, ['include_ticket_groups' => $isTicketingEnabled]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $this->get('security.password_encoder')->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($password);

            $this->get(UserFacade::class)->handleCreate($user);

            $this->addTranslatedFlash('success', 'User has been created.');

            return $this->redirectToRoute('user_show', ['id' => $user->getId()]);
        }

        return $this->render(
            'user/new.html.twig',
            [
                'user' => $user,
                'form' => $form->createView(),
                'isTicketingEnabled' => $isTicketingEnabled,
            ]
        );
    }

    /**
     * @Route("/{id}", name="user_show", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showAction(User $user): Response
    {
        $this->notDeleted($user);

        return $this->render(
            'user/show.html.twig',
            [
                'user' => $user,
                'isTicketingEnabled' => $this->getOption(Option::TICKETING_ENABLED),
            ]
        );
    }

    /**
     * @Route("/{id}/edit", name="user_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, User $user): Response
    {
        $this->notDeleted($user);

        $isTicketingEnabled = $this->getOption(Option::TICKETING_ENABLED);
        $form = $this->createForm(UserType::class, $user, ['include_ticket_groups' => $isTicketingEnabled]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && ! $user->getIsActive()) {
            if ($user->getRole() === User::ROLE_SUPER_ADMIN) {
                $form->get('isActive')->addError(new FormError('You cannot deactivate super admin account.'));
            } elseif ($user === $this->getUser()) {
                $form->get('isActive')->addError(new FormError('You cannot deactivate yourself.'));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            if ($user->getPlainPassword()) {
                $password = $this->get('security.password_encoder')->encodePassword($user, $user->getPlainPassword());
                $user->setPassword($password);
            }

            $this->get(UserFacade::class)->handleUpdate($user);
            $this->get(LocaleSessionUpdater::class)->update($user);

            $this->addTranslatedFlash('success', 'User has been saved.');

            return $this->redirectToRoute('user_show', ['id' => $user->getId()]);
        }

        return $this->render(
            'user/edit.html.twig',
            [
                'user' => $user,
                'form' => $form->createView(),
                'isTicketingEnabled' => $isTicketingEnabled,
            ]
        );
    }

    /**
     * @Route("/{id}/delete", name="user_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(User $user): Response
    {
        $this->notDeleted($user);

        if ($user->getRole() === User::ROLE_SUPER_ADMIN) {
            $this->addTranslatedFlash('error', 'You cannot delete super admin account.');
        } elseif ($user === $this->getUser()) {
            $this->addTranslatedFlash('error', 'You cannot delete yourself.');
        } else {
            $this->get(UserFacade::class)->handleUserDelete($user);

            $this->addTranslatedFlash('success', 'User has been deleted.');
        }

        return $this->redirectToRoute('user_index');
    }
}
