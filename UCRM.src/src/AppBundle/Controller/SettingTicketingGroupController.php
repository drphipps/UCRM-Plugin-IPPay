<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TicketingBundle\Entity\TicketGroup;
use TicketingBundle\Form\TicketGroupType;
use TicketingBundle\Service\Facade\TicketGroupFacade;

/**
 * @Route("/system/settings/ticketing/group")
 * @PermissionControllerName(SettingController::class)
 */
class SettingTicketingGroupController extends BaseController
{
    /**
     * @Route("/new", name="setting_ticketing_group_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request): Response
    {
        return $this->handleNewEdit($request);
    }

    /**
     * @Route("/{id}/edit", name="setting_ticketing_group_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, TicketGroup $ticketGroup): Response
    {
        return $this->handleNewEdit($request, $ticketGroup);
    }

    /**
     * @Route("/{id}/delete", name="setting_ticketing_group_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("edit")
     * @CsrfToken()
     */
    public function deleteAction(TicketGroup $ticketGroup): RedirectResponse
    {
        $this->get(TicketGroupFacade::class)->handleDelete($ticketGroup);

        $this->addTranslatedFlash('success', 'Item has been removed.');

        return $this->redirectToRoute('setting_ticketing_edit');
    }

    private function handleNewEdit(Request $request, ?TicketGroup $ticketGroup = null): Response
    {
        $isEdit = (bool) $ticketGroup;
        if (! $isEdit) {
            $ticketGroup = new TicketGroup();
        }

        $formAction = $isEdit
            ? $this->generateUrl('setting_ticketing_group_edit', ['id' => $ticketGroup->getId()])
            : $this->generateUrl('setting_ticketing_group_new');

        $form = $this->createForm(TicketGroupType::class, $ticketGroup, ['action' => $formAction]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ticketGroupFacade = $this->get(TicketGroupFacade::class);

            if ($isEdit) {
                $ticketGroupFacade->handleUpdate($ticketGroup);
                $this->addTranslatedFlash('success', 'Item has been saved.');
            } else {
                $ticketGroupFacade->handleCreate($ticketGroup);
                $this->addTranslatedFlash('success', 'Item has been created.');
            }

            return $this->createAjaxRedirectResponse('setting_ticketing_edit');
        }

        return $this->render(
            'setting/ticketing/ticketing_group_modal.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }
}
