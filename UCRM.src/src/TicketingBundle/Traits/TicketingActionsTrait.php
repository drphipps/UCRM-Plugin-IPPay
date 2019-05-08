<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Traits;

use AppBundle\Component\Elastic\Search;
use AppBundle\DataProvider\ContactTypeDataProvider;
use AppBundle\DataProvider\InvoiceDataProvider;
use AppBundle\DataProvider\LastSeenTicketCommentDataProvider;
use AppBundle\Entity\Client;
use AppBundle\Entity\ClientContact;
use AppBundle\Entity\Option;
use AppBundle\Exception\ElasticsearchException;
use AppBundle\Exception\FlashMessageExceptionInterface;
use AppBundle\Facade\ClientFacade;
use AppBundle\Security\Permission;
use AppBundle\Service\ExceptionStash;
use AppBundle\Util\DateTimeFactory;
use AppBundle\Util\Helpers;
use Doctrine\ORM\EntityManagerInterface;
use Elastica\Exception\ConnectionException;
use Elastica\Exception\ResponseException;
use SchedulingBundle\DataProvider\JobDataProvider;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Form\Type\JobSimpleType;
use SchedulingBundle\Security\SchedulingPermissions;
use SchedulingBundle\Service\Facade\JobFacade;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TicketingBundle\Component\TicketingRoutesMap;
use TicketingBundle\DataProvider\TicketActivityDataProvider;
use TicketingBundle\DataProvider\TicketCannedResponseDataProvider;
use TicketingBundle\DataProvider\TicketDataProvider;
use TicketingBundle\DataProvider\TicketGroupDataProvider;
use TicketingBundle\Entity\Ticket;
use TicketingBundle\Entity\TicketGroup;
use TicketingBundle\Entity\TicketImapEmailBlacklist;
use TicketingBundle\Form\Data\TicketAssignData;
use TicketingBundle\Form\Data\TicketCommentUserData;
use TicketingBundle\Form\Data\TicketDeleteData;
use TicketingBundle\Form\TicketAssignType;
use TicketingBundle\Form\TicketCommentType;
use TicketingBundle\Form\TicketDeleteType;
use TicketingBundle\Interfaces\TicketingActionsInterface;
use TicketingBundle\Request\TicketsRequest;
use TicketingBundle\Service\Facade\TicketCommentFacade;
use TicketingBundle\Service\Facade\TicketFacade;
use TicketingBundle\Service\Facade\TicketImapEmailBlacklistFacade;

/**
 * Provides implementation for all ticketing actions.
 * Needed because we need different layouts and URLs for each controller with ticketing component.
 * As of now, it's TicketController for general Ticketing module and ClientController for client detail module.
 *
 * @property Container              $container
 * @property EntityManagerInterface $em
 */
trait TicketingActionsTrait
{
    /**
     * @var TicketingRoutesMap|null
     */
    private $ticketingRoutesMap;

    private function handleTicketView(
        string $template,
        Request $request,
        ?Ticket $ticket,
        ?Client $client
    ): Response {
        try {
            $lastTimestamp = $request->query->get('lastTimestamp');
            if ($lastTimestamp) {
                $lastTimestamp = DateTimeFactory::createFromFormat(\DateTime::ATOM, $lastTimestamp);
            }
        } catch (\InvalidArgumentException $exception) {
            $lastTimestamp = null;
        }
        $hasNextPage = false;
        $statusFilters = $client ? [] : $this->getTicketStatusFilters($request);
        $userFilterKey = $this->getTicketUserFilterKey($request);

        $lastActivityFilter = $request->get(
            'last-activity-filter',
            TicketingActionsInterface::LAST_ACTIVITY_FILTER_ALL
        );
        /** @var TicketGroupDataProvider $ticketGroupDataProvider */
        $ticketGroupDataProvider = $this->container->get(TicketGroupDataProvider::class);
        $userFilter = null;
        $groupFilter = null;
        if ($userFilterKey === TicketingActionsInterface::USER_FILTER_MY) {
            $userFilter = $this->getUser();
        } elseif (is_int($userFilterKey)) {
            $groupFilter = $ticketGroupDataProvider->find($userFilterKey);
        } else {
            $userFilter = $userFilterKey;
        }
        /** @var TicketDataProvider $ticketDataProvider */
        $ticketDataProvider = $this->container->get(TicketDataProvider::class);
        $search = trim((string) $request->get('search', ''));
        $searchResultIds = null;
        if ($search) {
            try {
                $searchResultIds = $this->container->get(Search::class)->search(
                    Search::TYPE_TICKET,
                    $search,
                    true,
                    TicketingActionsInterface::SEARCH_ITEMS_LIMIT
                );
            } catch (ResponseException | ConnectionException | ElasticsearchException $exception) {
                $this->addTranslatedFlash(
                    'error',
                    'Could not process search because of Elasticsearch error: %error%',
                    null,
                    [
                        '%error%' => $exception->getMessage(),
                    ]
                );
                $searchResultIds = null;
            }
        }
        if ($searchResultIds !== null) {
            $tickets = $ticketDataProvider->getByIds(
                $searchResultIds,
                $statusFilters,
                $userFilter,
                $lastActivityFilter,
                $groupFilter
            );
        } else {
            $ticketsRequest = new TicketsRequest();
            $ticketsRequest->client = $client;
            $ticketsRequest->lastTimestamp = $lastTimestamp;
            $ticketsRequest->limit = TicketingActionsInterface::ITEMS_PER_PAGE + 1;
            $ticketsRequest->statusFilters = $statusFilters;
            $ticketsRequest->userFilter = $userFilter;
            $ticketsRequest->groupFilter = $groupFilter;
            $ticketsRequest->lastActivityFilter = $lastActivityFilter;

            $tickets = $ticketDataProvider->getTickets($ticketsRequest);
            $hasNextPage = count($tickets) === TicketingActionsInterface::ITEMS_PER_PAGE + 1;
            $tickets = array_slice($tickets, 0, TicketingActionsInterface::ITEMS_PER_PAGE);
        }

        $ticketGroups = $ticketGroupDataProvider->findAllForm();
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
                        '@Ticketing/user/tickets/components/ticket_list_page.html.twig',
                        [
                            'search' => $search,
                            'statusFilters' => $statusFilters,
                            'userFilter' => $userFilterKey,
                            'client' => $client,
                            'tickets' => $tickets,
                            'hasNextPage' => $hasNextPage,
                            'ticketingRoutesMap' => $this->getTicketingRoutesMap(),
                            'ticketGroups' => $ticketGroups,
                            'lastActivityFilter' => $lastActivityFilter,
                            'lastSeenTicketComments' => $this->container->get(LastSeenTicketCommentDataProvider::class)
                                ->getLastSeenTicketComments($this->getUser(), false),
                        ]
                    );
                case TicketingActionsInterface::AJAX_IDENTIFIER_FILTER:
                    $this->invalidateTemplate(
                        'ticketing-list-container',
                        '@Ticketing/user/tickets/components/ticket_list.html.twig',
                        [
                            'search' => $search,
                            'statusFilters' => $statusFilters,
                            'userFilter' => $userFilterKey,
                            'client' => $client,
                            'tickets' => $tickets,
                            'hasNextPage' => $hasNextPage,
                            'ticketingRoutesMap' => $this->getTicketingRoutesMap(),
                            'ticketGroups' => $ticketGroups,
                            'lastActivityFilter' => $lastActivityFilter,
                            'lastSeenTicketComments' => $this->container->get(LastSeenTicketCommentDataProvider::class)
                                ->getLastSeenTicketComments($this->getUser(), false),
                        ]
                    );

                    $this->invalidateTemplate(
                        'ticketing-filter',
                        '@Ticketing/user/tickets/components/ticket_filters.html.twig',
                        [
                            'search' => $search,
                            'statusFilters' => $statusFilters,
                            'userFilter' => $userFilterKey,
                            'ticketingRoutesMap' => $this->getTicketingRoutesMap(),
                            'ticketGroups' => $ticketGroups,
                            'lastActivityFilter' => $lastActivityFilter,
                        ]
                    );

                    return $this->createAjaxResponse(
                        [
                            'url' => [
                                'route' => $this->getTicketingRoutesMap()->view,
                                'parameters' => [
                                    'search' => $search,
                                    'status-filters' => $statusFilters,
                                    'user-filter' => $userFilterKey,
                                    'last-activity-filter' => $lastActivityFilter,
                                ],
                            ],
                        ]
                    );
                case TicketingActionsInterface::AJAX_IDENTIFIER_DETAIL:
                    if ($ticket) {
                        return $this->createTicketDetailResponse($ticket, $client);
                    }

                    break;
            }
        }

        $ticketDetailActivity = null;
        $ticketDetailJobWidgetVisibleIds = [];
        $ticketDetailCommentForm = null;
        $ticketNotFound = ! $ticket && $request->attributes->getInt('ticketId');
        $ticketDetail = $ticket ?? ($ticketNotFound ? null : reset($tickets));
        if ($ticketDetail) {
            $ticketData = new TicketCommentUserData();
            $ticketData->ticket = $ticketDetail;
            $ticketDetailCommentForm = $this->createTicketCommentForm($ticketDetail, $ticketData, $client);
            $ticketDetailCommentForm->handleRequest($request);

            if ($ticketDetailCommentForm->isSubmitted()) {
                $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);

                if ($ticketDetailCommentForm->isValid()) {
                    if ($ticketData->attachmentFiles && Helpers::isDemo()) {
                        $ticketData->attachmentFiles = [];
                        $this->addTranslatedFlash('error', 'File upload is is not available in the demo.');
                    }

                    $this->container->get(TicketCommentFacade::class)->handleNewFromData($ticketData, $this->getUser());

                    if ($ticketDetail->getStatus() === Ticket::STATUS_NEW) {
                        $this->container->get(TicketFacade::class)->handleStatusChange(
                            $ticketDetail,
                            Ticket::STATUS_OPEN
                        );
                    }

                    $this->addTranslatedFlash('success', 'Comment has been created.');

                    foreach ($this->container->get(ExceptionStash::class)->getAll() as $message => $exception) {
                        if ($exception instanceof FlashMessageExceptionInterface) {
                            $this->addTranslatedFlash('error', $message, null, $exception->getParameters());
                        } else {
                            $this->addTranslatedFlash('error', $message);
                        }
                    }

                    if ($request->isXmlHttpRequest()) {
                        // If valid let the invalidate create new form to reset data.
                        $this->invalidateOpenTicket($ticketDetail, null, $client);
                    } else {
                        return $this->redirectToRoute(
                            'ticketing_index',
                            [
                                'ticketId' => $ticketDetail->getId(),
                            ]
                        );
                    }
                } else {
                    $this->invalidateOpenTicket($ticket, $ticketDetailCommentForm, $client);
                }

                return $this->createAjaxResponse();
            }

            $ticketActivityDataProvider = $this->container->get(TicketActivityDataProvider::class);
            $ticketDetailActivity = $ticketActivityDataProvider->getByTicket($ticketDetail, 'ASC');
            $ticketDetailJobWidgetVisibleIds = $ticketActivityDataProvider->getJobWidgetVisibleIds(
                $ticketDetailActivity
            );
            $this->container->get(TicketFacade::class)->handleTicketShown($ticketDetail, $this->getUser());

            if ($this->isGranted(Permission::VIEW, SchedulingPermissions::JOBS_MY)) {
                $jobs = $this->container->get(JobDataProvider::class)->getJobsForTicket(
                    $ticketDetail,
                    $this->isGranted(Permission::VIEW, SchedulingPermissions::JOBS_ALL)
                        ? null
                        : $this->getUser()
                );
            } else {
                $jobs = [];
            }

            // @todo should be available even for only JOBS_MY edit permission,
            // but as we *must* assign user and it's not yet possible to do that without date
            // this has to wait for now
            if ($this->isGranted(Permission::EDIT, SchedulingPermissions::JOBS_ALL)) {
                $newJobForm = $this->createNewJobForm($ticketDetail, $client)->createView();
            }
        }

        return $this->render(
            $template,
            [
                'search' => $search,
                'statusFilters' => $statusFilters,
                'userFilter' => $userFilterKey,
                'client' => $client,
                'tickets' => $tickets,
                'hasNextPage' => $hasNextPage,
                'ticketDetail' => $ticketDetail,
                'ticketNotFound' => $ticketNotFound,
                'ticketDetailActivity' => $ticketDetailActivity,
                'ticketDetailJobWidgetVisibleIds' => $ticketDetailJobWidgetVisibleIds,
                'ticketDetailCommentForm' => $ticketDetailCommentForm ? $ticketDetailCommentForm->createView() : null,
                'ticketingRoutesMap' => $this->getTicketingRoutesMap(),
                'ticketingCannedResponses' => $this->container->get(TicketCannedResponseDataProvider::class)
                    ->getAllPairs(),
                'lastActivityFilter' => $lastActivityFilter,
                'ticketGroups' => $ticketGroups,
                'lastSeenTicketComments' => $this->container->get(LastSeenTicketCommentDataProvider::class)
                    ->getLastSeenTicketComments($this->getUser(), false),
                'jobs' => $jobs ?? [],
                'hasContactEmail' => $ticketDetail && $ticketDetail->getClient()
                    ? (bool) $ticketDetail->getClient()->getContactEmails()
                    : false,
                'notificationTicketUserCreatedByEmail' => $this->getOption(
                    Option::NOTIFICATION_TICKET_USER_CREATED_BY_EMAIL
                ),
                'assignedJobs' => $ticketDetail && ! $ticketDetail->getJobs()->isEmpty()
                    ? $this->em->getRepository(Job::class)->convertToDateAssoc($ticketDetail->getJobs()->toArray())
                    : [],
                'newJobForm' => $newJobForm ?? null,
                'showProformaInvoices' => $this->get(InvoiceDataProvider::class)->showProformaInvoices(),
            ]
        );
    }

    private function getTicketStatusFilters(Request $request): array
    {
        $statusFilters = $request->get('status-filters', []);
        $defaultStatusFilters = [
            Ticket::STATUS_NEW_KEY => true,
            Ticket::STATUS_OPEN_KEY => true,
            Ticket::STATUS_PENDING_KEY => true,
            Ticket::STATUS_SOLVED_KEY => false,
        ];
        $statusFilters = array_merge(
            $defaultStatusFilters,
            $statusFilters
        );
        $statusFilters = Helpers::typeCastAll('bool', $statusFilters);

        if (! array_filter($statusFilters)) {
            return $defaultStatusFilters;
        }

        return $statusFilters;
    }

    /**
     * @return int|string
     */
    private function getTicketUserFilterKey(Request $request)
    {
        $userFilterKey = $request->query->get('user-filter');
        if (is_numeric($userFilterKey)) {
            return (int) $userFilterKey;
        }

        if (
            ! is_string($userFilterKey)
            || ! in_array($userFilterKey, TicketingActionsInterface::POSSIBLE_USER_FILTERS, true)
        ) {
            $userFilterKey = TicketingActionsInterface::USER_FILTER_ALL;
        }

        return $userFilterKey;
    }

    private function handleTicketStatusEdit(
        Request $request,
        Ticket $ticket,
        int $status,
        ?Client $client = null
    ): Response {
        if ($ticket->getStatus() !== $status && array_key_exists($status, Ticket::STATUSES)) {
            $this->container->get(TicketFacade::class)->handleStatusChange($ticket, $status);
        }

        $this->addTranslatedFlash('success', 'Status has been updated.');

        if ($request->isXmlHttpRequest()) {
            $this->invalidateOpenTicket($ticket, null, $client);

            return $this->createAjaxResponse();
        }

        return $this->redirectToRoute(
            $this->getTicketingRoutesMap()->view,
            [
                'ticketId' => $ticket->getId(),
                'clientId' => $client ? $client->getId() : null,
            ]
        );
    }

    private function handleTicketGroupEdit(
        Request $request,
        Ticket $ticket,
        ?TicketGroup $ticketGroup,
        ?Client $client = null
    ): Response {
        if ($ticket->getGroup() !== $ticketGroup) {
            $this->container->get(TicketFacade::class)->handleTicketGroupChange($ticket, $ticketGroup);
        }

        $this->addTranslatedFlash('success', 'Group has been updated.');

        if ($request->isXmlHttpRequest()) {
            $this->invalidateOpenTicket($ticket, null, $client);

            return $this->createAjaxResponse();
        }

        return $this->redirectToRoute(
            $this->getTicketingRoutesMap()->view,
            [
                'ticketId' => $ticket->getId(),
                'clientId' => $client ? $client->getId() : null,
            ]
        );
    }

    private function handleTicketAddJob(
        Request $request,
        Ticket $ticket,
        Job $job,
        ?Client $client = null
    ): Response {
        // adding the job to ticket must have job permissions handled, as admins can only see jobs to add
        // filtered by their permissions
        if ($job->getAssignedUser() === $this->getUser()) {
            $this->denyAccessUnlessPermissionGranted(Permission::VIEW, SchedulingPermissions::JOBS_MY);
        } else {
            $this->denyAccessUnlessPermissionGranted(Permission::VIEW, SchedulingPermissions::JOBS_ALL);
        }

        if (! $ticket->getJobs()->contains($job)) {
            $this->container->get(TicketFacade::class)->handleAddJob($ticket, $job);
        }

        if ($request->isXmlHttpRequest()) {
            $this->invalidateOpenTicket($ticket, null, $client, null, TicketingActionsInterface::FORM_TAB_LINKED_JOBS);

            return $this->createAjaxResponse();
        }

        return $this->redirectToRoute(
            $this->getTicketingRoutesMap()->view,
            [
                'ticketId' => $ticket->getId(),
                'clientId' => $client ? $client->getId() : null,
            ]
        );
    }

    private function handleTicketRemoveJob(
        Request $request,
        Ticket $ticket,
        Job $job,
        ?Client $client = null
    ): Response {
        // opposed to adding job to ticket, the admins can already see the job as it now belongs to ticket,
        // so removing it is allowed regardless the job permissions

        if ($ticket->getJobs()->contains($job)) {
            $this->container->get(TicketFacade::class)->handleRemoveJob($ticket, $job);
        }

        if ($request->isXmlHttpRequest()) {
            $this->invalidateOpenTicket(
                $ticket,
                null,
                $client,
                null,
                $ticket->getJobs()->isEmpty()
                    ? TicketingActionsInterface::FORM_TAB_REPLY
                    : TicketingActionsInterface::FORM_TAB_LINKED_JOBS
            );

            return $this->createAjaxResponse();
        }

        return $this->redirectToRoute(
            $this->getTicketingRoutesMap()->view,
            [
                'ticketId' => $ticket->getId(),
                'clientId' => $client ? $client->getId() : null,
            ]
        );
    }

    private function handleTicketDelete(Request $request, Ticket $ticket, ?Client $client = null): Response
    {
        $ticketId = $ticket->getId();
        $this->container->get(TicketFacade::class)->handleDelete($ticket);

        $this->addTranslatedFlash('success', 'Ticket has been deleted.');

        if ($request->isXmlHttpRequest()) {
            $this->invalidateDeletedTicket($ticketId, $client);

            return $this->createAjaxResponse();
        }

        return $this->redirectToRoute(
            $this->getTicketingRoutesMap()->view,
            [
                'clientId' => $client ? $client->getId() : null,
            ]
        );
    }

    private function handleTicketDeleteFromImap(Request $request, Ticket $ticket, ?Client $client = null): Response
    {
        $url = $this->generateUrl(
            $this->getTicketingRoutesMap()->deleteFromImap,
            [
                'ticketId' => $ticket->getId(),
                'clientId' => $client ? $client->getId() : null,
            ]
        );

        $ticketDeleteData = new TicketDeleteData();

        /** @var FormInterface $form */
        $form = $this->createForm(
            TicketDeleteType::class,
            $ticketDeleteData,
            ['action' => $url, 'hasEmailFromAddress' => (bool) $ticket->getEmailFromAddress()]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $emailFromAddress = $ticket->getEmailFromAddress();
            $this->handleTicketDelete($request, $ticket);
            $countTickets = 1;
            if ($emailFromAddress) {
                if ($ticketDeleteData->addToBlacklist) {
                    $ticketImapEmailBlacklist = new TicketImapEmailBlacklist();
                    $ticketImapEmailBlacklist->setEmailAddress($emailFromAddress);
                    $this->container->get(TicketImapEmailBlacklistFacade::class)->handleCreate(
                        $ticketImapEmailBlacklist
                    );
                }
                if ($ticketDeleteData->deleteTickets) {
                    $countTickets += $this->container->get(TicketFacade::class)
                        ->deleteByEmailFromAddresses([$emailFromAddress]);
                }
            }

            if ($emailFromAddress && $ticketDeleteData->deleteTickets) {
                $this->addTranslatedFlash(
                    'success',
                    '%count% items will be deleted in the background within a few minutes.',
                    $countTickets,
                    [
                        '%count%' => $countTickets,
                    ]
                );
            } else {
                $this->addTranslatedFlash('success', 'Ticket has been deleted.');
            }

            return $this->createAjaxRedirectResponse(
                $this->getTicketingRoutesMap()->view,
                [
                    'clientId' => $client ? $client->getId() : null,
                ]
            );
        }

        return $this->render(
            '@Ticketing/user/ticket/ticket_delete_modal.html.twig',
            [
                'form' => $form->createView(),
                'ticket' => $ticket,
            ]
        );
    }

    private function handleTicketAssign(Request $request, Ticket $ticket, ?Client $client = null): Response
    {
        $url = $this->generateUrl(
            $this->getTicketingRoutesMap()->assign,
            [
                'ticketId' => $ticket->getId(),
                'clientId' => $client ? $client->getId() : null,
            ]
        );

        $data = new TicketAssignData();
        $data->assignedUser = $ticket->getAssignedUser();
        $data->assignedClient = $ticket->getClient();

        /** @var FormInterface $form */
        $form = $this->createForm(
            TicketAssignType::class,
            $data,
            [
                'action' => $url,
                'include_add_contact_option' => $ticket->getEmailFromAddress() !== null && ! $ticket->getClient(),
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($data->assignedClient === null && $ticket->getEmailFromAddress() === null) {
                $form->get('assignedClient')->addError(
                    new FormError('You can only remove assigned client from IMAP created tickets.')
                );
            }

            if ($form->isValid()) {
                $this->container->get(TicketFacade::class)->handleAssign($ticket, $data);

                if ($data->addContact && $data->assignedClient) {
                    $clientContact = new ClientContact();
                    $clientContact->setEmail($ticket->getEmailFromAddress());
                    $clientContact->setName($ticket->getEmailFromName());
                    $clientContact->addType($this->container->get(ContactTypeDataProvider::class)->getGeneralType());

                    $this->container->get(ClientFacade::class)->handleAddContact(
                        $data->assignedClient,
                        $clientContact
                    );
                }

                $this->addTranslatedFlash('success', 'Ticket assignment has been changed.');

                if ($request->isXmlHttpRequest()) {
                    // If assign request came from client's ticketing view and the client is different after change,
                    // we need to redirect to the new client or to the general ticketing if the client was unassigned.
                    // Otherwise we just invalidate the ticket templates.
                    if ($client && $data->assignedClient !== $client) {
                        if ($data->assignedClient) {
                            return $this->createAjaxRedirectResponse(
                                'client_show_tickets',
                                [
                                    'clientId' => $data->assignedClient->getId(),
                                    'ticketId' => $ticket->getId(),
                                ]
                            );
                        }

                        return $this->createAjaxRedirectResponse(
                            'ticketing_index',
                            [
                                'ticketId' => $ticket->getId(),
                            ]
                        );
                    }

                    $this->invalidateOpenTicket($ticket, null, $client);

                    return $this->createAjaxResponse();
                }

                return $this->redirectToRoute(
                    $this->getTicketingRoutesMap()->view,
                    [
                        'ticketId' => $ticket->getId(),
                        'clientId' => $client ? $client->getId() : null,
                    ]
                );
            }
        }

        return $this->render(
            '@Ticketing/client/assign_modal.html.twig',
            [
                'form' => $form->createView(),
                'ticketingRoutesMap' => $this->getTicketingRoutesMap(),
                'client' => $client,
            ]
        );
    }

    private function handleTicketNewJob(Request $request, Ticket $ticket, ?Client $client = null): Response
    {
        // @todo should be available even for only JOBS_MY edit permission,
        // but as we *must* assign user and it's not yet possible to do that without date
        // this has to wait for now
        $this->denyAccessUnlessPermissionGranted(Permission::EDIT, SchedulingPermissions::JOBS_ALL);

        $job = new Job();
        $form = $this->createNewJobForm($ticket, $client, $job);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->container->get(JobFacade::class)->handleNew($job);
            $this->container->get(TicketFacade::class)->handleAddJob($ticket, $job);
            $this->addTranslatedFlash('success', 'Job has been added.');

            $scheduleButton = $form->get('schedule');
            assert($scheduleButton instanceof SubmitButton);
            if ($scheduleButton->isClicked()) {
                return $this->createAjaxRedirectResponse(
                    'scheduling_timeline_index',
                    [
                        'queue' => true,
                    ]
                );
            }

            $this->invalidateOpenTicket($ticket, null, $client, null, TicketingActionsInterface::FORM_TAB_ADD_JOB);

            return $this->createAjaxResponse();
        }

        // remove job from the ticket to prevent cascade persist of invalid job
        $ticket->removeJob($job);

        $this->invalidateOpenTicket($ticket, null, $client, $form, TicketingActionsInterface::FORM_TAB_ADD_JOB);

        return $this->createAjaxResponse();
    }

    private function createTicketDetailResponse(Ticket $ticket, ?Client $client = null): Response
    {
        $this->invalidateTicketDetail($ticket, $client);

        return $this->createAjaxResponse(
            [
                'url' => [
                    'route' => $this->getTicketingRoutesMap()->view,
                    'parameters' => [
                        'ticketId' => $ticket->getId(),
                        'clientId' => $client ? $client->getId() : null,
                    ],
                ],
            ]
        );
    }

    private function createTicketCommentForm(
        Ticket $ticket,
        ?TicketCommentUserData $ticketData = null,
        ?Client $client = null
    ): FormInterface {
        if (! $ticketData) {
            $ticketData = new TicketCommentUserData();
            $ticketData->ticket = $ticket;
        }

        return $this->createForm(
            TicketCommentType::class,
            $ticketData,
            [
                'action' => $this->generateUrl(
                    $this->getTicketingRoutesMap()->view,
                    [
                        'ticketId' => $ticket->getId(),
                        'clientId' => $client ? $client->getId() : null,
                    ]
                ),
                'show_private_toggle' => $ticket->isPublic(),
                'attr' => [
                    'autocomplete' => 'off',
                ],
            ]
        );
    }

    private function createNewJobForm(Ticket $ticket, ?Client $client = null, ?Job $job = null): FormInterface
    {
        $job = $job ?? new Job();
        if ($client) {
            $job->setClient($client);
            $job->setAddress($client->getAddressString());
        } elseif ($ticket->getClient()) {
            $job->setClient($ticket->getClient());
            $job->setAddress($ticket->getClient()->getAddressString());
        }

        $form = $this->createForm(
            JobSimpleType::class,
            $job,
            [
                'action' => $this->generateUrl(
                    $this->getTicketingRoutesMap()->jobCreate,
                    [
                        'ticketId' => $ticket->getId(),
                        'clientId' => $client ? $client->getId() : null,
                    ]
                ),
            ]
        );

        return $form;
    }

    /**
     * Used to render the ticket detail with AJAX. Replaces content of the detail container.
     */
    private function invalidateTicketDetail(Ticket $ticket, ?Client $client = null): void
    {
        $this->container->get(TicketFacade::class)->handleTicketShown($ticket, $this->getUser());
        $ticketGroups = $this->container->get(TicketGroupDataProvider::class)->findAllForm();

        if ($this->isGranted(Permission::VIEW, SchedulingPermissions::JOBS_MY)) {
            $jobs = $this->container->get(JobDataProvider::class)->getJobsForTicket(
                $ticket,
                $this->isGranted(Permission::VIEW, SchedulingPermissions::JOBS_ALL)
                    ? null
                    : $this->getUser()
            );
        } else {
            $jobs = [];
        }

        // @todo should be available even for only JOBS_MY edit permission,
        // but as we *must* assign user and it's not yet possible to do that without date
        // this has to wait for now
        if ($this->isGranted(Permission::EDIT, SchedulingPermissions::JOBS_ALL)) {
            $newJobForm = $this->createNewJobForm($ticket, $client)->createView();
        }

        $ticketActivityDataProvider = $this->container->get(TicketActivityDataProvider::class);
        $ticketDetailActivity = $this->container->get(TicketActivityDataProvider::class)->getByTicket($ticket, 'ASC');
        $ticketDetailJobWidgetVisibleIds = $ticketActivityDataProvider->getJobWidgetVisibleIds($ticketDetailActivity);

        $this->invalidateTemplate(
            'ticketing-detail-container',
            '@Ticketing/user/tickets/components/ticket_detail.html.twig',
            [
                'client' => $client,
                'ticketDetail' => $ticket,
                'ticketDetailActivity' => $ticketDetailActivity,
                'ticketDetailJobWidgetVisibleIds' => $ticketDetailJobWidgetVisibleIds,
                'ticketDetailCommentForm' => $this->createTicketCommentForm($ticket, null, $client)->createView(),
                'ticketingRoutesMap' => $this->getTicketingRoutesMap(),
                'ticketingCannedResponses' => $this->container->get(TicketCannedResponseDataProvider::class)
                    ->getAllPairs(),
                'ticketGroups' => $ticketGroups,
                'jobs' => $jobs,
                'hasContactEmail' => $ticket->getClient() ? (bool) $ticket->getClient()->getContactEmails() : false,
                'notificationTicketUserCreatedByEmail' => $this->getOption(
                    Option::NOTIFICATION_TICKET_USER_CREATED_BY_EMAIL
                ),
                'assignedJobs' => ! $ticket->getJobs()->isEmpty()
                    ? $this->em->getRepository(Job::class)->convertToDateAssoc($ticket->getJobs()->toArray())
                    : [],
                'newJobForm' => $newJobForm ?? null,
            ]
        );

        $this->invalidateTemplate(
            'ticket-list__item-' . $ticket->getId(),
            '@Ticketing/user/tickets/components/ticket_list_item.html.twig',
            [
                'client' => $client,
                'ticket' => $ticket,
                'ticketingRoutesMap' => $this->getTicketingRoutesMap(),
                'lastSeenTicketComments' => $this->container->get(LastSeenTicketCommentDataProvider::class)
                    ->getLastSeenTicketComments($this->getUser(), false),
                'ticketGroups' => $ticketGroups,
            ],
            true
        );
    }

    /**
     * Used to render the changed ticket only when it's already present in the page.
     */
    private function invalidateOpenTicket(
        Ticket $ticket,
        ?FormInterface $ticketDetailCommentForm = null,
        ?Client $client = null,
        ?FormInterface $newJobForm = null,
        string $openFormTab = TicketingActionsInterface::FORM_TAB_REPLY
    ): void {
        $this->container->get(TicketFacade::class)->handleTicketShown($ticket, $this->getUser());
        $ticketGroups = $this->container->get(TicketGroupDataProvider::class)->findAllForm();

        if ($this->isGranted(Permission::VIEW, SchedulingPermissions::JOBS_MY)) {
            $jobs = $this->container->get(JobDataProvider::class)->getJobsForTicket(
                $ticket,
                $this->isGranted(Permission::VIEW, SchedulingPermissions::JOBS_ALL)
                    ? null
                    : $this->getUser()
            );
        } else {
            $jobs = [];
        }

        $ticketActivityDataProvider = $this->container->get(TicketActivityDataProvider::class);
        $ticketDetailActivity = $this->container->get(TicketActivityDataProvider::class)->getByTicket($ticket, 'ASC');
        $ticketDetailJobWidgetVisibleIds = $ticketActivityDataProvider->getJobWidgetVisibleIds($ticketDetailActivity);

        $this->invalidateTemplate(
            'ticket-detail-' . $ticket->getId(),
            '@Ticketing/user/tickets/components/ticket_detail.html.twig',
            [
                'client' => $client,
                'ticketDetail' => $ticket,
                'ticketDetailActivity' => $ticketDetailActivity,
                'ticketDetailJobWidgetVisibleIds' => $ticketDetailJobWidgetVisibleIds,
                'ticketDetailCommentForm' => $ticketDetailCommentForm
                    ? $ticketDetailCommentForm->createView()
                    : $this->createTicketCommentForm($ticket, null, $client)->createView(),
                'ticketingRoutesMap' => $this->getTicketingRoutesMap(),
                'ticketingCannedResponses' => $this->container->get(TicketCannedResponseDataProvider::class)
                    ->getAllPairs(),
                'ticketGroups' => $ticketGroups,
                'jobs' => $jobs,
                'hasContactEmail' => $ticket->getClient() ? (bool) $ticket->getClient()->getContactEmails() : false,
                'notificationTicketUserCreatedByEmail' => $this->getOption(
                    Option::NOTIFICATION_TICKET_USER_CREATED_BY_EMAIL
                ),
                'assignedJobs' => ! $ticket->getJobs()->isEmpty()
                    ? $this->em->getRepository(Job::class)->convertToDateAssoc($ticket->getJobs()->toArray())
                    : [],
                'openFormTab' => $openFormTab,
                'newJobForm' => $newJobForm
                    ? $newJobForm->createView()
                    : $this->isGranted(Permission::EDIT, SchedulingPermissions::JOBS_ALL)
                        ? $this->createNewJobForm($ticket, $client)->createView()
                        : null,
            ],
            true
        );

        $this->invalidateTemplate(
            'ticket-list__item-' . $ticket->getId(),
            '@Ticketing/user/tickets/components/ticket_list_item.html.twig',
            [
                'client' => $client,
                'ticket' => $ticket,
                'ticketingRoutesMap' => $this->getTicketingRoutesMap(),
                'lastSeenTicketComments' => $this->container->get(LastSeenTicketCommentDataProvider::class)
                    ->getLastSeenTicketComments($this->getUser(), false),
                'ticketGroups' => $ticketGroups,
            ],
            true
        );
    }

    /**
     * Used to remove the deleted ticket if it's present somewhere in the page content.
     */
    private function invalidateDeletedTicket(int $ticketId, ?Client $client = null): void
    {
        $this->invalidateTemplate(
            'ticket-detail-' . $ticketId,
            '@Ticketing/user/tickets/components/ticket_detail.html.twig',
            [
                'ticketDetail' => null,
                'ticketDetailActivity' => null,
                'ticketDetailJobWidgetVisibleIds' => [],
                'ticketDetailCommentForm' => null,
                'client' => $client,
                'ticketingRoutesMap' => $this->getTicketingRoutesMap(),
            ],
            true
        );

        $this->invalidateTemplate(
            'ticket-list__item-' . $ticketId,
            '@Ticketing/user/tickets/components/ticket_list_item.html.twig',
            [
                'ticket' => null,
                'client' => $client,
                'ticketingRoutesMap' => $this->getTicketingRoutesMap(),
            ],
            true
        );
    }
}
