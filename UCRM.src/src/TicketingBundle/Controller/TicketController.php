<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Controller;

use AppBundle\Controller\BaseController;
use AppBundle\Exception\FlashMessageExceptionInterface;
use AppBundle\Security\Permission;
use AppBundle\Service\ExceptionStash;
use AppBundle\Util\Helpers;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use SchedulingBundle\Entity\Job;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TicketingBundle\Component\TicketingRoutesMap;
use TicketingBundle\DataProvider\TicketCannedResponseDataProvider;
use TicketingBundle\Entity\Ticket;
use TicketingBundle\Entity\TicketGroup;
use TicketingBundle\Form\Data\TicketNewUserData;
use TicketingBundle\Form\TicketUserType;
use TicketingBundle\Interfaces\TicketingActionsInterface;
use TicketingBundle\Service\Facade\TicketFacade;
use TicketingBundle\Traits\TicketingActionsTrait;

/**
 * @Route("/ticketing")
 */
class TicketController extends BaseController implements TicketingActionsInterface
{
    use TicketingActionsTrait;

    /**
     * @Route(
     *     "/{ticketId}",
     *     name="ticketing_index",
     *     defaults={"ticketId": null},
     *     requirements={"ticketId": "\d+"}
     * )
     * @Method({"GET", "POST"})
     * @ParamConverter("ticket", options={"id" = "ticketId"})
     * @Permission("view")
     */
    public function indexAction(Request $request, ?Ticket $ticket = null): Response
    {
        return $this->handleTicketView('@Ticketing/user/tickets/show.html.twig', $request, $ticket, null);
    }

    /**
     * @Route(
     *     "/ticket/{ticketId}/assign",
     *     name="ticketing_client_ticket_assign",
     *     requirements={"ticketId": "\d+"}
     * )
     * @Method({"GET", "POST"})
     * @Permission("edit")
     * @ParamConverter("ticket", options={"id" = "ticketId"})
     */
    public function assignAction(Request $request, Ticket $ticket): Response
    {
        return $this->handleTicketAssign($request, $ticket);
    }

    /**
     * @Route(
     *     "/ticket/{ticketId}/status-edit/{status}",
     *     name="ticketing_ticket_status_edit",
     *     requirements={"ticketId": "\d+", "status": "\d+"}
     * )
     * @Method("GET")
     * @Permission("edit")
     * @CsrfToken()
     * @ParamConverter("ticket", options={"id" = "ticketId"})
     */
    public function statusEditAction(Request $request, Ticket $ticket, int $status): Response
    {
        return $this->handleTicketStatusEdit($request, $ticket, $status);
    }

    /**
     * @Route(
     *     "/ticket/{ticketId}/ticket-group-edit/{ticketGroupId}",
     *     name="ticketing_ticket_group_edit",
     *     requirements={"ticketId": "\d+", "ticketGroupId": "\d+"},
     *     defaults={"ticketGroupId": null}
     * )
     * @Method("GET")
     * @Permission("edit")
     * @CsrfToken()
     * @ParamConverter("ticket", options={"id" = "ticketId"})
     * @ParamConverter("ticketGroup", options={"id" = "ticketGroupId"})
     */
    public function ticketGroupEditAction(Request $request, Ticket $ticket, ?TicketGroup $ticketGroup = null): Response
    {
        return $this->handleTicketGroupEdit($request, $ticket, $ticketGroup);
    }

    /**
     * @Route(
     *     "/ticket/{ticketId}/job-add/{jobId}",
     *     name="ticketing_ticket_job_add",
     *     requirements={"ticketId": "\d+", "jobId": "\d+"}
     * )
     * @Method("GET")
     * @Permission("edit")
     * @CsrfToken()
     * @ParamConverter("ticket", options={"id" = "ticketId"})
     * @ParamConverter("job", options={"id" = "jobId"})
     */
    public function ticketAddJobAction(Request $request, Ticket $ticket, Job $job): Response
    {
        return $this->handleTicketAddJob($request, $ticket, $job);
    }

    /**
     * @Route(
     *     "/ticket/{ticketId}/job-remove/{jobId}",
     *     name="ticketing_ticket_job_remove",
     *     requirements={"ticketId": "\d+", "jobId": "\d+"}
     * )
     * @Method("GET")
     * @Permission("edit")
     * @CsrfToken()
     * @ParamConverter("ticket", options={"id" = "ticketId"})
     * @ParamConverter("job", options={"id" = "jobId"})
     */
    public function ticketRemoveJobAction(Request $request, Ticket $ticket, Job $job): Response
    {
        return $this->handleTicketRemoveJob($request, $ticket, $job);
    }

    /**
     * @Route(
     *     "/ticket/{ticketId}/job-create",
     *     name="ticketing_ticket_job_create",
     *     requirements={"ticketId": "\d+"}
     * )
     * @Method({"GET", "POST"})
     * @Permission("edit")
     * @ParamConverter("ticket", options={"id" = "ticketId"})
     */
    public function ticketCreateJobAction(Request $request, Ticket $ticket): Response
    {
        return $this->handleTicketNewJob($request, $ticket);
    }

    /**
     * @Route(
     *     "/ticket/new",
     *     name="ticketing_ticket_new",
     * )
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request): Response
    {
        $data = new TicketNewUserData();
        $form = $this->createForm(
            TicketUserType::class,
            $data,
            [
                'action' => $this->generateUrl('ticketing_ticket_new'),
                'attr' => [
                    'autocomplete' => 'off',
                ],
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($data->attachmentFiles && Helpers::isDemo()) {
                $data->attachmentFiles = [];
                $this->addTranslatedFlash('error', 'File upload is is not available in the demo.');
            }

            $this->get(TicketFacade::class)->handleNewFromUserData($data, $this->getUser());

            $this->addTranslatedFlash('success', 'Ticket has been created.');
            foreach ($this->get(ExceptionStash::class)->getAll() as $message => $exception) {
                if ($exception instanceof FlashMessageExceptionInterface) {
                    $this->addTranslatedFlash('error', $message, null, $exception->getParameters());
                } else {
                    $this->addTranslatedFlash('error', $message);
                }
            }

            return $this->createAjaxRedirectResponse('ticketing_index');
        }

        return $this->render(
            '@Ticketing/user/ticket/new_modal.html.twig',
            [
                'form' => $form->createView(),
                'ticketingRoutesMap' => $this->getTicketingRoutesMap(),
            ]
        );
    }

    /**
     * @Route("/{ticketId}/delete", name="ticketing_ticket_delete", requirements={"ticketId": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @ParamConverter("ticket", options={"id" = "ticketId"})
     * @Permission("edit")
     */
    public function deleteAction(Request $request, Ticket $ticket): Response
    {
        return $this->handleTicketDelete($request, $ticket);
    }

    /**
     * @Route("/{ticketId}/delete-from-imap", name="ticketing_ticket_delete_from_imap", requirements={"ticketId": "\d+"})
     * @Method({"GET", "POST"})
     * @ParamConverter("ticket", options={"id" = "ticketId"})
     * @Permission("edit")
     */
    public function deleteFromImapAction(Request $request, Ticket $ticket): Response
    {
        return $this->handleTicketDeleteFromImap($request, $ticket);
    }

    /**
     * @Route("/canned-responses", name="ticketing_canned_responses_get", options={"expose"=true})
     * @Permission("view")
     * @Method("GET")
     */
    public function getCannedResponsesAction(): Response
    {
        return new JsonResponse($this->get(TicketCannedResponseDataProvider::class)->getAllPairs());
    }

    public function getTicketingRoutesMap(): TicketingRoutesMap
    {
        if (! $this->ticketingRoutesMap) {
            $map = new TicketingRoutesMap();
            $map->view = 'ticketing_index';
            $map->delete = 'ticketing_ticket_delete';
            $map->statusEdit = 'ticketing_ticket_status_edit';
            $map->ticketGroupEdit = 'ticketing_ticket_group_edit';
            $map->assign = 'ticketing_client_ticket_assign';
            $map->deleteFromImap = 'ticketing_ticket_delete_from_imap';
            $map->jobAdd = 'ticketing_ticket_job_add';
            $map->jobRemove = 'ticketing_ticket_job_remove';
            $map->jobCreate = 'ticketing_ticket_job_create';

            $this->ticketingRoutesMap = $map;
        }

        return $this->ticketingRoutesMap;
    }
}
