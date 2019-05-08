<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller\ClientZone;

use AppBundle\DataProvider\LastSeenTicketCommentDataProvider;
use AppBundle\Entity\Client;
use AppBundle\Entity\Option;
use AppBundle\Exception\FlashMessageExceptionInterface;
use AppBundle\Exception\NoClientContactException;
use AppBundle\Exception\PublicUrlGeneratorException;
use AppBundle\Security\Permission;
use AppBundle\Service\DownloadResponseFactory;
use AppBundle\Service\ExceptionStash;
use AppBundle\Util\DateTimeFactory;
use AppBundle\Util\Helpers;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TicketingBundle\DataProvider\TicketActivityDataProvider;
use TicketingBundle\DataProvider\TicketDataProvider;
use TicketingBundle\Entity\Ticket;
use TicketingBundle\Entity\TicketCommentAttachment;
use TicketingBundle\Entity\TicketCommentMailAttachment;
use TicketingBundle\FileManager\CommentAttachmentFileManager;
use TicketingBundle\Form\ClientZoneTicketType;
use TicketingBundle\Form\Data\TicketCommentClientData;
use TicketingBundle\Form\Data\TicketNewData;
use TicketingBundle\Form\TicketCommentType;
use TicketingBundle\Interfaces\TicketingActionsInterface;
use TicketingBundle\Request\TicketsRequest;
use TicketingBundle\Service\Facade\SupportFormFacade;
use TicketingBundle\Service\Facade\TicketCommentFacade;
use TicketingBundle\Service\Facade\TicketFacade;
use TicketingBundle\Service\Factory\TicketImapModelFactory;

/**
 * SECURITY WARNING: This route "/client-zone/support" is fully accessible for ROLE_ADMIN_OR_CLIENT
 * (see app/config/security.yml) so every Action method needs handle this.
 *
 * @Route("/client-zone/support")
 */
class SupportController extends BaseController
{
    /**
     * @Route(
     *     "/{ticketId}",
     *     name="client_zone_support_index",
     *     defaults={"ticketId": null},
     *     requirements={"ticketId": "\d+"}
     * )
     * @Method({"GET", "POST"})
     * @ParamConverter("ticket", options={"id" = "ticketId"})
     * @Permission("guest")
     */
    public function indexAction(Request $request, ?Ticket $ticket = null): Response
    {
        if ($this->getUser()->isAdmin()) {
            return $this->redirectToRoute(
                'ticketing_index',
                [
                    'ticketId' => $ticket ? $ticket->getId() : null,
                ]
            );
        }

        if ($ticket) {
            $this->verifyOwnership($ticket);

            if (! $ticket->isPublic()) {
                throw $this->createAccessDeniedException();
            }
        }

        $ticketingEnabled = $this->getOption(Option::TICKETING_ENABLED);
        if ($ticketingEnabled) {
            $ticketDataProvider = $this->get(TicketDataProvider::class);
            $ticketsRequest = new TicketsRequest();
            $ticketsRequest->client = $this->getClient();
            $ticketsRequest->public = true;
            $ticketsRequest->limit = 1;

            if (! $ticketDataProvider->getTickets($ticketsRequest)) {
                $ticketingEnabled = false;
            }
        }

        if ($ticketingEnabled) {
            return $this->renderTicketingEnabled($request, $ticket);
        }

        return $this->renderTicketingDisabled($request);
    }

    /**
     * @deprecated use indexAction with ticketId, kept because links with this were sent to client emails
     *
     * @Route("/ticket/{id}", name="client_zone_ticket_show", requirements={"id": "\d+"})
     * @Method({"GET"})
     * @Permission("guest")
     */
    public function ticketShowAction(Ticket $ticket): Response
    {
        return $this->redirectToRoute(
            'client_zone_support_index',
            [
                'ticketId' => $ticket->getId(),
            ]
        );
    }

    /**
     * @Route("/ticket/comment/attachment/{id}", name="client_zone_ticket_comment_attachment_get", requirements={"id": "\d+"})
     * @Method({"GET"})
     * @Permission("guest")
     */
    public function getAttachment(TicketCommentAttachment $ticketCommentAttachment): Response
    {
        if ($this->getUser()->isAdmin()) {
            return $this->redirectToRoute(
                'ticketing_comment_attachment_get',
                [
                    'id' => $ticketCommentAttachment->getId(),
                ]
            );
        }

        $ticketComment = $ticketCommentAttachment->getTicketComment();
        $ticket = $ticketComment->getTicket();
        if (
            $ticket->getClient() !== $this->getClient()
            || ! $ticket->isPublic()
            || ! $ticketComment->isPublic()
        ) {
            throw $this->createAccessDeniedException();
        }

        return $this->get(DownloadResponseFactory::class)->createFromFile(
            $this->get(CommentAttachmentFileManager::class)->getFilePath($ticketCommentAttachment),
            $ticketCommentAttachment->getOriginalFilename(),
            $ticketCommentAttachment->getMimeType()
        );
    }

    /**
     * @Route("/ticket/comment/mail-attachment/{id}", name="client_zone_ticket_comment_mail_attachment_get", requirements={"id": "\d+"})
     * @Method({"GET"})
     * @Permission("guest")
     */
    public function getCommentMailAttachment(TicketCommentMailAttachment $ticketCommentMailAttachment): Response
    {
        if ($this->getUser()->isAdmin()) {
            return $this->redirectToRoute(
                'ticketing_comment_mail_attachment_get',
                [
                    'id' => $ticketCommentMailAttachment->getId(),
                ]
            );
        }

        $ticketComment = $ticketCommentMailAttachment->getTicketComment();
        $ticket = $ticketComment->getTicket();
        if (
            $ticket->getClient() !== $this->getClient()
            || ! $ticket->isPublic()
            || ! $ticketComment->isPublic()
        ) {
            throw $this->createAccessDeniedException();
        }

        try {
            return $this->get(DownloadResponseFactory::class)->createFromContent(
                $this->get(TicketImapModelFactory::class)
                    ->create(
                        $ticketCommentMailAttachment->getTicketComment()->getInbox()
                    )->getAttachmentContent($ticketCommentMailAttachment),
                $ticketCommentMailAttachment->getFilename()
            );
        } catch (\Exception $exception) {
            $this->addTranslatedFlash('error', 'Attachment is not available.');

            return $this->redirectToRoute(
                'client_zone_support_index',
                [
                    'ticketId' => $ticket->getId(),
                ]
            );
        }
    }

    private function renderTicketingEnabled(Request $request, ?Ticket $ticketDetail): Response
    {
        $client = $this->getClient();
        try {
            $lastTimestamp = $request->query->get('lastTimestamp');
            if ($lastTimestamp) {
                $lastTimestamp = DateTimeFactory::createFromFormat(\DateTime::ATOM, $lastTimestamp);
            }
        } catch (\InvalidArgumentException $exception) {
            $lastTimestamp = null;
        }

        $ticketDataProvider = $this->get(TicketDataProvider::class);
        $ticketsRequest = new TicketsRequest();
        $ticketsRequest->client = $client;
        $ticketsRequest->lastTimestamp = $lastTimestamp;
        $ticketsRequest->limit = TicketingActionsInterface::ITEMS_PER_PAGE + 1;
        $ticketsRequest->public = true;

        $tickets = $ticketDataProvider->getTickets($ticketsRequest);
        $hasNextPage = count($tickets) === TicketingActionsInterface::ITEMS_PER_PAGE + 1;
        $tickets = array_slice($tickets, 0, TicketingActionsInterface::ITEMS_PER_PAGE);

        $organization = $client->getOrganization();

        if ($request->isXmlHttpRequest()) {
            // AJAX_IDENTIFIER is used to distinguish between AJAX requests to this URL.
            // For example if paginating we want to render only the ticket list.

            switch ($request->get(TicketingActionsInterface::AJAX_IDENTIFIER)) {
                case TicketingActionsInterface::AJAX_IDENTIFIER_PAGINATION:
                    // If there are no tickets on the "next" page, return empty response, not the "There are not tickets" message
                    if (! $tickets) {
                        return new Response();
                    }

                    return $this->render(
                        'client_zone/support/components/ticket_list/page.html.twig',
                        [
                            'client' => $client,
                            'tickets' => $tickets,
                            'hasNextPage' => $hasNextPage,
                            'lastSeenTicketComments' => $this->get(LastSeenTicketCommentDataProvider::class)
                                ->getLastSeenTicketComments($client->getUser(), true),
                        ]
                    );
                case TicketingActionsInterface::AJAX_IDENTIFIER_DETAIL:
                    if ($ticketDetail) {
                        return $this->createTicketDetailResponse($ticketDetail);
                    }

                    break;
            }
        }

        $data = new TicketNewData();
        $data->client = $client;
        $newTicketForm = $this->createForm(
            ClientZoneTicketType::class,
            $data
        );
        $newTicketForm->handleRequest($request);

        if ($newTicketForm->isSubmitted() && $newTicketForm->isValid()) {
            return $this->handleNewTicketFormSubmit($data, $client);
        }

        $ticketDetailCommentForm = null;
        $ticketDetailActivity = null;
        if ($ticketDetail) {
            $ticketData = new TicketCommentClientData();
            $ticketData->ticket = $ticketDetail;

            $ticketDetailCommentForm = $this->createTicketCommentForm($ticketDetail, $ticketData);
            $ticketDetailCommentForm->handleRequest($request);

            if ($ticketDetailCommentForm->isSubmitted()) {
                if ($ticketDetailCommentForm->isValid()) {
                    if ($ticketData->attachmentFiles && Helpers::isDemo()) {
                        $ticketData->attachmentFiles = [];
                        $this->addTranslatedFlash('error', 'File upload is is not available in the demo.');
                    }

                    $this->get(TicketCommentFacade::class)->handleNewFromClientData($ticketData);

                    $this->addTranslatedFlash('success', 'Comment has been created.');

                    foreach ($this->get(ExceptionStash::class)->getAll() as $message => $exception) {
                        if ($exception instanceof FlashMessageExceptionInterface) {
                            $this->addTranslatedFlash('error', $message, null, $exception->getParameters());
                        } else {
                            $this->addTranslatedFlash('error', $message);
                        }
                    }

                    if ($request->isXmlHttpRequest()) {
                        // If valid let the invalidate create new form to reset data.
                        $this->invalidateOpenTicket($ticketDetail);
                    } else {
                        return $this->redirectToRoute(
                            'client_zone_support_index',
                            [
                                'ticketId' => $ticketDetail->getId(),
                            ]
                        );
                    }
                } else {
                    $this->invalidateOpenTicket($ticketDetail, $ticketDetailCommentForm);
                }

                return $this->createAjaxResponse();
            }

            $ticketDetailActivity = $this->get(TicketActivityDataProvider::class)
                ->getPublicCommentsByTicket($ticketDetail, 'ASC');
            $this->get(TicketFacade::class)->handleTicketShown($ticketDetail, $client->getUser());
        }

        $ticketNotFound = ! $ticketDetail && $request->attributes->getInt('ticketId');
        if ($ticketNotFound && $request->isXmlHttpRequest()) {
            $this->invalidateTemplate(
                'ticketing-detail-container',
                'client_zone/support/components/ticket_detail/detail.html.twig',
                [
                    'client' => $client,
                    'ticketNotFound' => true,
                ]
            );

            $this->invalidateTemplate(
                'ticket-detect',
                'client_zone/support/components/ticket_detect.html.twig',
                [
                    'ticketNotFound' => true,
                ],
                true
            );

            return $this->createAjaxResponse();
        }

        return $this->render(
            'client_zone/support/ticketing_enabled.html.twig',
            [
                'client' => $client,
                'organization' => $organization,
                'supportEmail' => $this->getOption(Option::SUPPORT_EMAIL_ADDRESS) ?: $organization->getEmail(),
                'tickets' => $tickets,
                'hasNextPage' => $hasNextPage,
                'newTicketForm' => $newTicketForm->createView(),
                'ticketDetail' => $ticketDetail,
                'ticketDetailActivity' => $ticketDetailActivity,
                'ticketDetailCommentForm' => $ticketDetailCommentForm ? $ticketDetailCommentForm->createView() : null,
                'ticketNotFound' => $ticketNotFound,
                'lastSeenTicketComments' => $this->get(LastSeenTicketCommentDataProvider::class)
                    ->getLastSeenTicketComments($client->getUser(), true),
                'forceNewTicketForm' => $request->query->getBoolean('contact'),
            ]
        );
    }

    private function renderTicketingDisabled(Request $request): Response
    {
        $failedRecipients = null;
        $exception = null;
        $client = $this->getClient();
        $organization = $client->getOrganization();

        $supportEmail = $this->getOption(Option::SUPPORT_EMAIL_ADDRESS) ?: $organization->getEmail();
        $data = new TicketNewData();
        $data->client = $client;
        $newTicketForm = $this->createForm(
            ClientZoneTicketType::class,
            $data
        );
        $newTicketForm->handleRequest($request);

        if ($newTicketForm->isSubmitted() && $newTicketForm->isValid()) {
            return $this->handleNewTicketFormSubmit($data, $client);
        }

        return $this->render(
            'client_zone/support/ticketing_disabled.html.twig',
            [
                'client' => $client,
                'organization' => $organization,
                'supportEmail' => $supportEmail,
                'newTicketForm' => $newTicketForm->createView(),
            ]
        );
    }

    private function handleNewTicketFormSubmit(TicketNewData $data, Client $client): Response
    {
        if ($data->attachmentFiles && Helpers::isDemo()) {
            $data->attachmentFiles = [];
            $this->addTranslatedFlash('error', 'File upload is is not available in the demo.');
        }

        $ticketingEnabled = $this->getOption(Option::TICKETING_ENABLED);
        if ($ticketingEnabled) {
            $this->get(TicketFacade::class)->handleNewFromData($data, $client);
        } else {
            $this->get(SupportFormFacade::class)->handleSupportForm(
                $client,
                $data->subject ?? '',
                $data->message ?? ''
            );
        }

        $exceptions = $this->get(ExceptionStash::class)->getAll();
        $messageSent = true;
        foreach ($exceptions as $exception) {
            if (
                $exception instanceof PublicUrlGeneratorException
                || $exception instanceof NoClientContactException
            ) {
                $messageSent = false;

                break;
            }
        }

        if ($messageSent) {
            $this->addTranslatedFlash('success', 'Message successfully sent.');
        } elseif ($ticketingEnabled) {
            $this->addTranslatedFlash('success', 'Ticket has been created.');
        } else {
            $this->addTranslatedFlash('error', 'Could not send email.');
        }

        return $this->redirectToRoute('client_zone_support_index');
    }

    private function createTicketDetailResponse(Ticket $ticket): Response
    {
        $client = $this->getClient();

        $this->get(TicketFacade::class)->handleTicketShown($ticket, $client->getUser());

        $this->invalidateTemplate(
            'ticketing-detail-container',
            'client_zone/support/components/ticket_detail/detail.html.twig',
            [
                'client' => $client,
                'ticketDetail' => $ticket,
                'ticketDetailActivity' => $this->get(TicketActivityDataProvider::class)->getPublicCommentsByTicket(
                    $ticket,
                    'ASC'
                ),
                'ticketDetailCommentForm' => $this->createTicketCommentForm($ticket)->createView(),
            ]
        );

        $this->invalidateTemplate(
            'ticket-detect',
            'client_zone/support/components/ticket_detect.html.twig',
            [
                'ticketDetail' => $ticket,
            ],
            true
        );

        $this->invalidateTemplate(
            'ticket-list__item-' . $ticket->getId(),
            'client_zone/support/components/ticket_list/item.html.twig',
            [
                'client' => $client,
                'ticket' => $ticket,
                'lastSeenTicketComments' => $this->get(LastSeenTicketCommentDataProvider::class)
                    ->getLastSeenTicketComments($client->getUser(), true),
            ],
            true
        );

        return $this->createAjaxResponse(
            [
                'url' => [
                    'route' => 'client_zone_support_index',
                    'parameters' => [
                        'ticketId' => $ticket->getId(),
                    ],
                ],
            ]
        );
    }

    private function createTicketCommentForm(
        Ticket $ticket,
        ?TicketCommentClientData $ticketData = null
    ): FormInterface {
        if (! $ticketData) {
            $ticketData = new TicketCommentClientData();
            $ticketData->ticket = $ticket;
        }

        return $this->createForm(
            TicketCommentType::class,
            $ticketData,
            [
                'action' => $this->generateUrl(
                    'client_zone_support_index',
                    [
                        'ticketId' => $ticket->getId(),
                    ]
                ),
                'show_private_toggle' => false,
            ]
        );
    }

    /**
     * Used to render the changed ticket only when it's already present in the page.
     */
    private function invalidateOpenTicket(
        Ticket $ticket,
        ?FormInterface $ticketDetailCommentForm = null
    ): void {
        $client = $this->getClient();
        $this->get(TicketFacade::class)->handleTicketShown($ticket, $client->getUser());

        $this->invalidateTemplate(
            'ticket-detail-' . $ticket->getId(),
            'client_zone/support/components/ticket_detail/detail.html.twig',
            [
                'client' => $client,
                'ticketDetail' => $ticket,
                'ticketDetailActivity' => $this->get(TicketActivityDataProvider::class)
                    ->getPublicCommentsByTicket($ticket, 'ASC'),
                'ticketDetailCommentForm' => $ticketDetailCommentForm
                    ? $ticketDetailCommentForm->createView()
                    : $this->createTicketCommentForm($ticket)->createView(),
            ],
            true
        );

        $this->invalidateTemplate(
            'ticket-list__item-' . $ticket->getId(),
            'client_zone/support/components/ticket_list/item.html.twig',
            [
                'client' => $client,
                'ticket' => $ticket,
                'lastSeenTicketComments' => $this->get(LastSeenTicketCommentDataProvider::class)
                    ->getLastSeenTicketComments($client->getUser(), true),
            ],
            true
        );

        $this->invalidateTemplate(
            'ticket-detect',
            'client_zone/support/components/ticket_detect.html.twig',
            [
                'ticketDetail' => $ticket,
            ],
            true
        );
    }
}
