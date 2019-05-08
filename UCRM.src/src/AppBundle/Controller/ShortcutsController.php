<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Shortcuts\ShortcutFactory;
use AppBundle\DataProvider\ShortcutDataProvider;
use AppBundle\Entity\Shortcut;
use AppBundle\Entity\User;
use AppBundle\Facade\ShortcutFacade;
use AppBundle\Form\ShortcutType;
use AppBundle\Security\Permission;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Nette\Utils\Strings;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/shortcuts")
 */
class ShortcutsController extends BaseController
{
    /**
     * @Route("/new", name="shortcuts_new", options={"expose": true})
     * @Method({"GET", "POST"})
     * @Permission("guest")
     */
    public function newAction(Request $request): Response
    {
        return $this->handleNewEditAction($request, null);
    }

    /**
     * @Route("/{id}/edit", name="shortcuts_edit", options={"expose": true})
     * @Method({"GET", "POST"})
     * @Permission("guest")
     */
    public function editAction(Request $request, Shortcut $shortcut): Response
    {
        return $this->handleNewEditAction($request, $shortcut);
    }

    /**
     * @Route("/{id}/move", name="shortcuts_move", requirements={"shorcut": "\d+"}, options={"expose": true})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("guest")
     */
    public function moveAction(Request $request, Shortcut $shortcut): Response
    {
        if ($shortcut->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $shortcut->setSequence($request->query->getInt('sequence'));
        $this->get(ShortcutFacade::class)->handleEdit($shortcut);

        $this->addTranslatedFlash('success', 'Item has been saved.');

        return $this->createAjaxResponse();
    }

    /**
     * @Route("/{id}/delete", name="shortcuts_delete", requirements={"shorcut": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("guest")
     */
    public function deleteAction(Shortcut $shortcut): Response
    {
        $user = $this->getUser();
        if ($shortcut->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $this->get(ShortcutFacade::class)->handleDelete($shortcut);

        $this->addTranslatedFlash('success', 'Item has been removed.');
        $this->invalidateTemplate(
            'side-nav__shortcuts',
            'shortcuts/components/list.html.twig',
            [
                'shortcuts' => $this->get(ShortcutDataProvider::class)->get($user),
            ]
        );

        return $this->createAjaxResponse();
    }

    private function handleNewEditAction(Request $request, ?Shortcut $shortcut): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $isEdit = false;
        if ($shortcut) {
            if ($shortcut->getUser() !== $user) {
                throw $this->createAccessDeniedException();
            }

            $formAction = $this->generateUrl(
                'shortcuts_edit',
                [
                    'id' => $shortcut->getId(),
                ]
            );
            $isEdit = true;
        } else {
            $route = $request->query->get('route', '');
            try {
                $parameters = $request->query->get('parameters', '');
                if (! is_string($parameters)) {
                    $parameters = [];
                } else {
                    $parameters = Json::decode($parameters, Json::FORCE_ARRAY);
                }
            } catch (JsonException $jsonException) {
                $parameters = [];
            }
            $parameters = is_array($parameters) ? $parameters : [];
            $suffix = $request->query->get('suffix', null);
            if (! Strings::startsWith($suffix, '#')) {
                $suffix = null;
            }

            $shortcut = $this->get(ShortcutFactory::class)->create($user, $route, $parameters, $suffix);
            $formAction = $this->generateUrl(
                'shortcuts_new',
                [
                    'route' => $route,
                    'parameters' => Json::encode($parameters),
                    'suffix' => $suffix,
                ]
            );
        }

        try {
            $shortcutUrl = $this->generateUrl(
                    $shortcut->getRoute(),
                    $shortcut->getParameters()
                ) . ($shortcut->getSuffix() ?? '');
        } catch (\InvalidArgumentException $exception) {
            return $this->render(
                'shortcuts/form.html.twig',
                [
                    'error' => 'Can\'t generate shortcut URL.',
                    'shortcut' => $shortcut,
                ]
            );
        }

        $form = $this->createForm(
            ShortcutType::class,
            $shortcut,
            [
                'action' => $formAction,
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($isEdit) {
                $this->get(ShortcutFacade::class)->handleEdit($shortcut);
            } else {
                $this->get(ShortcutFacade::class)->handleNew($shortcut);
            }

            $this->addTranslatedFlash('success', 'Item has been saved.');
            $this->invalidateTemplate(
                'side-nav__shortcuts',
                'shortcuts/components/list.html.twig',
                [
                    'shortcuts' => $this->get(ShortcutDataProvider::class)->get($user),
                ]
            );

            return $this->createAjaxResponse();
        }

        return $this->render(
            'shortcuts/form.html.twig',
            [
                'shortcutUrl' => $shortcutUrl ?? null,
                'form' => $form->createView(),
                'shortcut' => $shortcut,
            ]
        );
    }
}
