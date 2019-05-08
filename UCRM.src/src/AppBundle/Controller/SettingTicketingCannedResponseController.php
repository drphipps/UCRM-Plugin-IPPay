<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use Doctrine\Common\Collections\ArrayCollection;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TicketingBundle\DataProvider\TicketCannedResponseDataProvider;
use TicketingBundle\Entity\TicketCannedResponse;
use TicketingBundle\Form\Data\TicketCannedResponseCollection;
use TicketingBundle\Form\TicketCannedResponseCollectionType;
use TicketingBundle\Form\TicketCannedResponseType;
use TicketingBundle\Service\Facade\TicketCannedResponseFacade;

/**
 * @Route("/system/settings/ticketing/canned-response")
 * @PermissionControllerName(SettingController::class)
 */
class SettingTicketingCannedResponseController extends BaseController
{
    /**
     * @Route("/new", name="setting_ticketing_canned_response_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request): Response
    {
        return $this->handleNewEdit($request);
    }

    /**
     * @Route("/{id}/edit", name="setting_ticketing_canned_response_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, TicketCannedResponse $ticketCannedResponse): Response
    {
        return $this->handleNewEdit($request, $ticketCannedResponse);
    }

    /**
     * @Route("/{id}/delete", name="setting_ticketing_canned_response_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(TicketCannedResponse $ticketCannedResponse): RedirectResponse
    {
        $this->get(TicketCannedResponseFacade::class)->handleDelete($ticketCannedResponse);

        $this->addTranslatedFlash('success', 'Item has been removed.');

        return $this->redirectToRoute('setting_ticketing_edit');
    }

    /**
     * @Route("/organize", name="setting_ticketing_canned_response_organize")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function organizeAction(Request $request): Response
    {
        $collection = new TicketCannedResponseCollection();
        $cannedResponses = $this->get(TicketCannedResponseDataProvider::class)->getAll();
        $collection->setTicketCannedResponses(new ArrayCollection($cannedResponses));
        $cannedResponsesBefore = clone $collection->getTicketCannedResponses();
        if ($request->query->getBoolean('new')) {
            $collection->getTicketCannedResponses()->add(new TicketCannedResponse());
        }

        $formAction = $this->generateUrl('setting_ticketing_canned_response_organize');
        $form = $this->createForm(
            TicketCannedResponseCollectionType::class,
            $collection,
            [
                'action' => $formAction,
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ticketCannedResponseFacade = $this->get(TicketCannedResponseFacade::class);

            $cannedResponsesAfter = $collection->getTicketCannedResponses();
            $ticketCannedResponseFacade->handleCollection($cannedResponsesBefore, $cannedResponsesAfter);

            return $this->createAjaxResponse();
        }

        return $this->render(
            'setting/ticketing/ticketing_canned_response_organize.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    private function handleNewEdit(Request $request, ?TicketCannedResponse $ticketCannedResponse = null): Response
    {
        $isEdit = (bool) $ticketCannedResponse;
        if (! $isEdit) {
            $ticketCannedResponse = new TicketCannedResponse();
        }

        $formAction = $isEdit
            ? $this->generateUrl('setting_ticketing_canned_response_edit', ['id' => $ticketCannedResponse->getId()])
            : $this->generateUrl('setting_ticketing_canned_response_new');

        $form = $this->createForm(TicketCannedResponseType::class, $ticketCannedResponse, ['action' => $formAction]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ticketCannedResponseFacade = $this->get(TicketCannedResponseFacade::class);

            if ($isEdit) {
                $ticketCannedResponseFacade->handleUpdate($ticketCannedResponse);
                $this->addTranslatedFlash('success', 'Item has been saved.');
            } else {
                $ticketCannedResponseFacade->handleCreate($ticketCannedResponse);
                $this->addTranslatedFlash('success', 'Item has been created.');
            }

            return $this->createAjaxRedirectResponse('setting_ticketing_edit');
        }

        return $this->render(
            'setting/ticketing/ticketing_canned_response_modal.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }
}
