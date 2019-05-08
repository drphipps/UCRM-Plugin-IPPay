<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use ApiBundle\Request\ServiceCollectionRequest;
use AppBundle\Component\Map\NetworkMapProvider;
use AppBundle\Component\Map\Request\NetworkMapRequest;
use AppBundle\Component\Map\ServicesMapProvider;
use AppBundle\DataProvider\ClientAirLinkDataProvider;
use AppBundle\DataProvider\ClientDataProvider;
use AppBundle\DataProvider\ClientLogsViewDataProvider;
use AppBundle\DataProvider\ClientTagDataProvider;
use AppBundle\DataProvider\InvoiceDataProvider;
use AppBundle\DataProvider\QuoteDataProvider;
use AppBundle\DataProvider\ServiceAirLinkDataProvider;
use AppBundle\DataProvider\ServiceDataProvider;
use AppBundle\Entity\Client;
use AppBundle\Entity\ClientAttribute;
use AppBundle\Entity\ClientBankAccount;
use AppBundle\Entity\ClientLog;
use AppBundle\Entity\ClientLogsView;
use AppBundle\Entity\ClientTag;
use AppBundle\Entity\EmailLog;
use AppBundle\Entity\EntityLog;
use AppBundle\Entity\Fee;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Option;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Payment;
use AppBundle\Entity\Refund;
use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceDevice;
use AppBundle\Exception\EmailAttachmentNotFoundException;
use AppBundle\Exception\FlashMessageExceptionInterface;
use AppBundle\Exception\NoClientContactException;
use AppBundle\Exception\PublicUrlGeneratorException;
use AppBundle\Exception\SequenceException;
use AppBundle\Facade\ClientBankAccountFacade;
use AppBundle\Facade\ClientFacade;
use AppBundle\Facade\ClientLogFacade;
use AppBundle\Facade\ClientLogsViewFacade;
use AppBundle\Facade\EmailLogFacade;
use AppBundle\Facade\Exception\CannotCancelClientSubscriptionException;
use AppBundle\Facade\Exception\CannotDeleteDemoClientException;
use AppBundle\Facade\PaymentFacade;
use AppBundle\Facade\RefundFacade;
use AppBundle\Facade\UserFacade;
use AppBundle\Facade\UserPersonalizationFacade;
use AppBundle\Factory\ClientFactory;
use AppBundle\Form\ClientBankAccountType;
use AppBundle\Form\ClientLogExportType;
use AppBundle\Form\ClientLogType;
use AppBundle\Form\ClientNoteType;
use AppBundle\Form\ClientType;
use AppBundle\Form\Data\StripeAchVerifyData;
use AppBundle\Form\RefundType;
use AppBundle\Form\StripeAchVerifyType;
use AppBundle\Grid\Client\ClientGridFactory;
use AppBundle\Grid\Client\ClientLeadsGridFactory;
use AppBundle\Grid\ClientLogsView\ClientLogsViewGridFactory;
use AppBundle\Grid\Invoice\ClientInvoiceGridFactory;
use AppBundle\Grid\Payment\ClientPaymentGridFactory;
use AppBundle\Grid\Refund\RefundGridFactory;
use AppBundle\Interfaces\InvoiceActionsInterface;
use AppBundle\RabbitMq\Client\SendInvitationEmailsMessage;
use AppBundle\RabbitMq\ClientLogsView\ExportClientLogsViewMessage;
use AppBundle\Request\QuoteCollectionRequest;
use AppBundle\RoutesMap\InvoiceRoutesMap;
use AppBundle\Security\Permission;
use AppBundle\Security\SpecialPermission;
use AppBundle\Service\ActionLogger;
use AppBundle\Service\Client\ClientBalanceFormatter;
use AppBundle\Service\ExceptionStash;
use AppBundle\Service\InvitationEmailSender;
use AppBundle\Service\ResetPasswordEmailSender;
use AppBundle\Util\Helpers;
use AppBundle\Util\Invoicing;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use RabbitMqBundle\RabbitMqEnqueuer;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Security\SchedulingPermissions;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Stripe\Error;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use TicketingBundle\Component\TicketingRoutesMap;
use TicketingBundle\Controller\TicketController;
use TicketingBundle\Entity\Ticket;
use TicketingBundle\Entity\TicketGroup;
use TicketingBundle\Form\Data\TicketNewUserData;
use TicketingBundle\Form\TicketUserType;
use TicketingBundle\Interfaces\TicketingActionsInterface;
use TicketingBundle\Service\Facade\TicketFacade;
use TicketingBundle\Traits\TicketingActionsTrait;

/**
 * @Route("/client")
 */
class ClientController extends BaseController implements TicketingActionsInterface, InvoiceActionsInterface
{
    use InvoiceActionsTrait;
    use TicketingActionsTrait;

    public const FILTER_ACTIVE = 'active';
    public const FILTER_ARCHIVE = 'archive';
    public const FILTER_LEAD = 'lead';

    public const AJAX_IDENTIFIER_CLIENT_LOG_FILTER = 'client-log-filter';

    /**
     * @Route("/{filterType}", name="client_index", defaults={"filterType" = "active"}, requirements={
     *     "filterType": "active|archive|lead"
     * })
     * @Method({"GET", "POST"})
     * @Permission("view")
     */
    public function indexAction(Request $request, string $filterType): Response
    {
        $clientDataProvider = $this->get(ClientDataProvider::class);
        $existsAny = $clientDataProvider->existsAny();
        if (! $existsAny) {
            return $this->render(
                'client/index_empty.html.twig',
                [
                    'filterType' => $filterType,
                    'filterTypeActive' => self::FILTER_ACTIVE,
                    'filterTypeArchive' => self::FILTER_ARCHIVE,
                    'filterTypeLead' => self::FILTER_LEAD,
                ]
            );
        }

        $activeCount = $clientDataProvider->getActiveCount();
        $leadCount = $clientDataProvider->getLeadCount();

        $commonParameters = [
            'filterType' => $filterType,
            'filterTypeActive' => self::FILTER_ACTIVE,
            'filterTypeArchive' => self::FILTER_ARCHIVE,
            'filterTypeLead' => self::FILTER_LEAD,
            'activeCount' => $activeCount,
            'leadCount' => $leadCount,
        ];

        if ($filterType === self::FILTER_ARCHIVE && $clientDataProvider->getArchiveCount() === 0) {
            return $this->render(
                'client/index_empty_archive.html.twig',
                $commonParameters
            );
        }

        if ($filterType === self::FILTER_LEAD && $leadCount === 0) {
            return $this->render(
                'client/index_empty_leads.html.twig',
                $commonParameters
            );
        }

        if ($filterType === self::FILTER_LEAD) {
            $grid = $this->get(ClientLeadsGridFactory::class)->create();
        } else {
            $grid = $this->get(ClientGridFactory::class)->create($filterType);
        }

        if ($response = $grid->processMultiAction()) {
            return $response;
        }
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'client/index.html.twig',
            array_merge(
                $commonParameters,
                [
                    'clientsGrid' => $grid,
                ]
            )
        );
    }

    /**
     * @Route("/new", name="client_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request): Response
    {
        $clientFactory = $this->get(ClientFactory::class);
        $client = $clientFactory->create(null);
        $clientFactory->addDefaultContactIfNeeded($client);

        if ($request->query->getBoolean('lead')) {
            $client->setIsLead(true);
        }

        $multipleTaxes = $this->getOption(Option::PRICING_MULTIPLE_TAXES);
        $form = $this->createForm(
            ClientType::class,
            $client,
            [
                'multipleTaxes' => $multipleTaxes,
                'firstClient' => ! (bool) $this->em->getRepository(Client::class)->getMaxClientId(),
                'includeOrganizationSelect' => $this->em->getRepository(Organization::class)->getCount() !== 1,
                'attr' => [
                    'autocomplete' => 'off',
                    'data-form-mode' => 'new',
                ],
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->has('clientId') && $clientId = $form->get('clientId')->getData()) {
                try {
                    $this->container->get(ClientFacade::class)->setNextClientId($clientId);
                } catch (SequenceException $e) {
                    $this->addTranslatedFlash(
                        'warning',
                        'New value for next client ID is lower or equal than max client ID.'
                    );
                }
            }

            $this->get(ClientFacade::class)->handleCreate($client);

            $this->addTranslatedFlash('success', 'Client has been created.');

            /** @var SubmitButton $sendAndSaveButton */
            $sendAndSaveButton = $form->get('sendAndSave');
            if (! $client->getIsLead() && $sendAndSaveButton->isClicked()) {
                try {
                    $this->get(InvitationEmailSender::class)->send($client);
                    $this->addTranslatedFlash('info', 'Invitation email has been sent.');
                } catch (PublicUrlGeneratorException | NoClientContactException $exception) {
                    $this->addTranslatedFlash('error', $exception->getMessage());

                    return $this->redirectToRoute(
                        'client_show',
                        [
                            'id' => $client->getId(),
                        ]
                    );
                }
            }

            return $this->redirectToRoute(
                'client_show',
                [
                    'id' => $client->getId(),
                ]
            );
        }

        return $this->render(
            'client/new.html.twig',
            [
                'client' => $client,
                'invoiceMaturityDays' => $client->getOrganization()
                    ? $client->getOrganization()->getInvoiceMaturityDays()
                    : null,
                'stopServiceDue' => $this->getOption(Option::STOP_SERVICE_DUE),
                'stopServiceDueDays' => $this->getOption(Option::STOP_SERVICE_DUE_DAYS),
                'lateFeeDelayDays' => $this->getOption(Option::LATE_FEE_DELAY_DAYS),
                'sendInvoiceByPost' => $this->getOption(Option::SEND_INVOICE_BY_POST),
                'generateProformaInvoices' => $this->getOption(Option::GENERATE_PROFORMA_INVOICES),
                'form' => $form->createView(),
                'multipleTaxes' => $multipleTaxes,
            ]
        );
    }

    /**
     * @Route("/{id}", name="client_show", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("view")
     */
    public function showAction(Request $request, int $id): Response
    {
        $client = $this->em->find(Client::class, $id);

        if (! $client) {
            throw new NotFoundHttpException(sprintf('%s object not found.', Client::class));
        }

        $userPersonalizationFacade = $this->get(UserPersonalizationFacade::class);
        $defaultView = $userPersonalizationFacade->getVisibleClientLogs($this->getUser()->getUserPersonalization());

        $logTypeFiltersQuery = $request->get('logType', $defaultView);
        $grid = $this->get(ClientLogsViewGridFactory::class)->create($client, ['logType' => $logTypeFiltersQuery]);

        if (array_diff($defaultView, $logTypeFiltersQuery) || array_diff($logTypeFiltersQuery, $defaultView)) {
            $userPersonalizationFacade->setVisibleClientLogs(
                $this->getUser()->getUserPersonalization(),
                $logTypeFiltersQuery
            );
        }

        if ($response = $grid->processMultiAction()) {
            return $response;
        }
        if ($parameters = $grid->processAjaxRequest($request)) {
            $this->invalidateTemplate(
                'client-log__footer',
                'client/components/view/client_log_footer.html.twig',
                [
                    'clientLogGrid' => $grid,
                ]
            );

            return $this->createAjaxResponse($parameters);
        }

        $noteForm = $this->createForm(ClientNoteType::class, $client);
        if ($noteFormResponse = $this->handleNoteForm($request, $client, $noteForm)) {
            return $noteFormResponse;
        }

        $newLogForm = $this->createAddClientLogForm($request, $client);
        $newLogFormResponse = $this->handleAddClientLogForm(
            $request,
            $client,
            $newLogForm,
            [
                'clientLogGrid' => $grid,
                'clientLogFiltersLink' => $this->createClientLogFiltersLinks($logTypeFiltersQuery),
                'clientLogTypeFilters' => $logTypeFiltersQuery,
            ]
        );
        if ($newLogFormResponse) {
            return $newLogFormResponse;
        }

        $logExportForm = $this->createLogExportForm($request, $client);
        $logExportFormResponse = $this->handleLogExportForm(
            $request,
            $client,
            ['logType' => $logTypeFiltersQuery],
            $logExportForm
        );
        if ($logExportFormResponse) {
            return $logExportFormResponse;
        }

        if ($request->isXmlHttpRequest()) {
            switch ($request->get(self::AJAX_IDENTIFIER)) {
                case self::AJAX_IDENTIFIER_CLIENT_LOG_FILTER:
                    $this->invalidateTemplate(
                        'client-log',
                        'client/components/view/client_log.html.twig',
                        [
                            'client' => $client,
                            'newLogForm' => $newLogForm->createView(),
                            'logExportForm' => $this->createLogExportForm($request, $client)->createView(),
                            'clientLogGrid' => $grid,
                            'clientLogFiltersLink' => $this->createClientLogFiltersLinks($logTypeFiltersQuery),
                            'clientLogTypeFilters' => $logTypeFiltersQuery,
                        ]
                    );

                    return $this->createAjaxResponse(
                        [
                            'url' => [
                                'route' => 'client_show',
                                'parameters' => [
                                    'id' => $id,
                                    'logType' => $logTypeFiltersQuery,
                                ],
                            ],
                        ]
                    );
            }
        }

        $this->em->getRepository(ClientAttribute::class)->loadAttributes($client);

        $organizationCount = $this->em->getRepository(Organization::class)->getCount();
        $fees = $this->em->getRepository(Fee::class)
            ->getClientUninvoicedFees($client);

        $serviceCollectionRequest = new ServiceCollectionRequest();
        $serviceCollectionRequest->clientId = $client->getId();

        $services = $this->get(ServiceDataProvider::class)->getCollection($serviceCollectionRequest);

        if ($this->isPermissionGranted(Permission::VIEW, InvoiceController::class)) {
            $invoices = $this->em->getRepository(Invoice::class)->findBy(
                [
                    'client' => $client,
                    'uncollectible' => false,
                ],
                [
                    'id' => 'DESC',
                ],
                3
            );
        }

        if ($this->isPermissionGranted(Permission::VIEW, QuoteController::class)) {
            $quoteCollectionRequest = new QuoteCollectionRequest();
            $quoteCollectionRequest->client = $client;
            $quoteCollectionRequest->limit = 3;
            $quoteCollectionRequest->order = 'createdDate';
            $quoteCollectionRequest->direction = 'DESC';
            $quotes = $this->get(QuoteDataProvider::class)->getQuotes($quoteCollectionRequest);
        }

        $jobsByDate = null;
        if ($this->isGranted(Permission::VIEW, SchedulingPermissions::JOBS_ALL)) {
            $jobsByDate = $this->em->getRepository(Job::class)->getByClientByDate($client, 5);
        } elseif ($this->isGranted(Permission::VIEW, SchedulingPermissions::JOBS_MY)) {
            $jobsByDate = $this->em->getRepository(Job::class)->getByClientByDate($client, 5, $this->getUser());
        }

        $leadMap = null;
        if ($client->getIsLead()) {
            $networkMapRequest = new NetworkMapRequest();
            $networkMapRequest->clientLead = $client;
            $leadMap = $this->get(NetworkMapProvider::class)->getData($networkMapRequest);
        }

        $airLinkAddress = $client->getShortAddressString();
        if (count($services) === 1) {
            /** @var Service $service */
            $service = reset($services);
            $airLinkUrl = $this->get(ServiceAirLinkDataProvider::class)->get($service);
            $airLinkAddress = $service->getShortAddressString();
        } elseif ($client->getIsLead()) {
            $airLinkUrl = $this->get(ClientAirLinkDataProvider::class)->get($client);
        }

        return $this->render(
            'client/show.html.twig',
            [
                'client' => $client,
                'nextInvoicingDay' => $this->em->getRepository(Service::class)->getClientNextInvoicingDay(
                    $client->getId()
                ),
                'fees' => $fees,
                'showFeeWarning' => ! Invoicing::isLikelyToHaveFutureInvoice(
                    $client,
                    $this->getOption(Option::STOP_INVOICING)
                ),
                'noteForm' => $noteForm->createView(),
                'newLogForm' => $newLogForm->createView(),
                'logExportForm' => $logExportForm->createView(),
                'organizationCount' => $organizationCount,
                'services' => $services,
                'servicesMap' => ! $leadMap ? $this->get(ServicesMapProvider::class)->getData($services) : null,
                'leadMap' => $leadMap,
                'invoices' => $invoices ?? [],
                'balance' => $this->get(ClientBalanceFormatter::class)->getFormattedBalanceRaw($client->getBalance()),
                'credit' => $client->getAccountStandingsCredit(),
                'outstanding' => $client->getAccountStandingsOutstanding(),
                'clientLogGrid' => $grid,
                'clientLogFiltersLink' => $this->createClientLogFiltersLinks($logTypeFiltersQuery),
                'clientLogTypeFilters' => $logTypeFiltersQuery,
                'deletedServiceDevices' => $this->em->getRepository(ServiceDevice::class)->getDeletedByClient($client),
                'jobsByDate' => $jobsByDate,
                'clientTags' => $this->get(ClientTagDataProvider::class)->getAllPossibleTagsForClient($client),
                'hasStripe' => $client->getOrganization()->hasStripe($this->isSandbox()),
                'hasStripeAch' => $client->getOrganization()->hasStripeAch($this->isSandbox()),
                'stripePubKey' => $client->getOrganization()->getStripePublishableKey($this->isSandbox()),
                'quotes' => $quotes ?? [],
                'linkedSubscriptionPossible' => $this->getOption(Option::SUBSCRIPTIONS_ENABLED_LINKED)
                    && $client->getOrganization()->hasPaymentProviderSupportingAutopay($this->isSandbox())
                    && $this->get(ServiceDataProvider::class)->getServicesForLinkedSubscriptions($client),
                'customSubscriptionPossible' => $this->getOption(Option::SUBSCRIPTIONS_ENABLED_CUSTOM)
                    && $client->getOrganization()->hasPaymentGateway($this->isSandbox()),
                'hasContactEmail' => (bool) $client->getContactEmails(),
                'airLinkUrl' => $airLinkUrl ?? null,
                'airLinkAddress' => $airLinkAddress,
                'showProformaInvoices' => $this->get(InvoiceDataProvider::class)->showProformaInvoices(),
            ]
        );
    }

    /**
     * @Route("/{id}/invoices", name="client_show_invoices", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showInvoicesAction(Request $request, int $id): Response
    {
        $this->denyAccessUnlessPermissionGranted(Permission::VIEW, InvoiceController::class);

        $client = $this->em->find(Client::class, $id);

        if (! $client || $client->getIsLead()) {
            throw $this->createNotFoundException();
        }

        $grid = $this->get(ClientInvoiceGridFactory::class)->createInvoiceGrid($client);
        if ($response = $grid->processMultiAction()) {
            return $response;
        }
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'client/show_invoices.html.twig',
            [
                'client' => $client,
                'grid' => $grid,
                'showProformaInvoices' => $this->get(InvoiceDataProvider::class)->showProformaInvoices(),
            ]
        );
    }

    /**
     * @Route("/{id}/proforma-invoices", name="client_show_proformas", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showProformaInvoicesAction(Request $request, int $id): Response
    {
        $this->denyAccessUnlessPermissionGranted(Permission::VIEW, InvoiceController::class);

        $client = $this->em->find(Client::class, $id);

        if (! $client || $client->getIsLead()) {
            throw $this->createNotFoundException();
        }

        $grid = $this->get(ClientInvoiceGridFactory::class)->createProformaGrid($client);
        if ($response = $grid->processMultiAction()) {
            return $response;
        }
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'client/show_invoices.html.twig',
            [
                'client' => $client,
                'grid' => $grid,
                'showProformaInvoices' => $this->get(InvoiceDataProvider::class)->showProformaInvoices(),
            ]
        );
    }

    /**
     * @Route("/{id}/payments", name="client_show_payments", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showPaymentsAction(Request $request, int $id): Response
    {
        $this->denyAccessUnlessPermissionGranted(Permission::VIEW, PaymentController::class);

        $client = $this->em->find(Client::class, $id);

        if (! $client || $client->getIsLead()) {
            throw $this->createNotFoundException();
        }

        $grid = $this->get(ClientPaymentGridFactory::class)->create($client);
        if ($response = $grid->processMultiAction()) {
            return $response;
        }
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'client/show_payments.html.twig',
            [
                'client' => $client,
                'grid' => $grid,
                'showProformaInvoices' => $this->get(InvoiceDataProvider::class)->showProformaInvoices(),
            ]
        );
    }

    /**
     * @Route("/{id}/refunds", name="client_show_refunds", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showRefundsAction(Request $request, int $id): Response
    {
        $this->denyAccessUnlessPermissionGranted(Permission::VIEW, RefundController::class);

        $client = $this->em->find(Client::class, $id);

        if (! $client || $client->getIsLead()) {
            throw $this->createNotFoundException();
        }

        $grid = $this->get(RefundGridFactory::class)->create($client);
        if ($response = $grid->processMultiAction()) {
            return $response;
        }
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'client/show_refunds.html.twig',
            [
                'client' => $client,
                'grid' => $grid,
                'showProformaInvoices' => $this->get(InvoiceDataProvider::class)->showProformaInvoices(),
            ]
        );
    }

    /**
     * @Route(
     *     "/{clientId}/tickets/{ticketId}",
     *     name="client_show_tickets",
     *     defaults={"ticketId": null},
     *     requirements={"clientId": "\d+", "ticketId": "\d+"}
     * )
     * @Method({"GET", "POST"})
     * @ParamConverter("ticket", options={"id" = "ticketId"})
     * @ParamConverter("client", options={"id" = "clientId"})
     * @Permission("view")
     */
    public function showTicketsAction(Request $request, Client $client, ?Ticket $ticket = null): Response
    {
        $this->denyAccessUnlessPermissionGranted(Permission::VIEW, TicketController::class);

        return $this->handleTicketView(
            'client/show_tickets.html.twig',
            $request,
            $ticket,
            $client
        );
    }

    /**
     * @Route("/{id}/ticket-new", name="client_ticket_new", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("view")
     */
    public function newTicketAction(Client $client, Request $request): Response
    {
        $this->denyAccessUnlessPermissionGranted(Permission::EDIT, TicketController::class);

        $url = $this->generateUrl(
            'client_ticket_new',
            [
                'id' => $client->getId(),
            ]
        );
        $data = new TicketNewUserData();
        $data->client = $client;
        $form = $this->createForm(
            TicketUserType::class,
            $data,
            [
                'action' => $url,
                'show_client_select' => false,
                'attr' => [
                    'autocomplete' => 'off',
                ],
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(TicketFacade::class)->handleNewFromUserData($data, $this->getUser());
            $this->addTranslatedFlash('success', 'Ticket has been created.');
            foreach ($this->get(ExceptionStash::class)->getAll() as $message => $exception) {
                if ($exception instanceof FlashMessageExceptionInterface) {
                    $this->addTranslatedFlash('error', $message, null, $exception->getParameters());
                } else {
                    $this->addTranslatedFlash('error', $message);
                }
            }

            return $this->createAjaxRedirectResponse(
                'client_show_tickets',
                [
                    'clientId' => $client->getId(),
                ]
            );
        }

        return $this->render(
            'client/tickets/new_ticket_modal.html.twig',
            [
                'form' => $form->createView(),
                'ticketingRoutesMap' => $this->getTicketingRoutesMap(),
                'hasContactEmail' => (bool) $client->getContactEmails(),
                'notificationTicketUserCreatedByEmail' => $this->getOption(
                    Option::NOTIFICATION_TICKET_USER_CREATED_BY_EMAIL
                ),
            ]
        );
    }

    /**
     * @Route(
     *     "/{clientId}/tickets/{ticketId}/delete",
     *     name="client_ticket_delete",
     *     requirements={"clientId": "\d+", "ticketId": "\d+"}
     * )
     * @Method("GET")
     * @ParamConverter("ticket", options={"id" = "ticketId"})
     * @ParamConverter("client", options={"id" = "clientId"})
     * @CsrfToken()
     * @Permission("view")
     */
    public function deleteTicketAction(Request $request, Ticket $ticket, Client $client): Response
    {
        $this->denyAccessUnlessPermissionGranted(Permission::EDIT, TicketController::class);

        return $this->handleTicketDelete($request, $ticket, $client);
    }

    /**
     * @Route(
     *     "/{clientId}/tickets/{ticketId}/delete-from-imap",
     *     name="client_ticket_delete_from_imap",
     *     requirements={"clientId": "\d+", "ticketId": "\d+"}
     * )
     * @Method({"GET", "POST"})
     * @ParamConverter("ticket", options={"id" = "ticketId"})
     * @ParamConverter("client", options={"id" = "clientId"})
     * @Permission("edit")
     */
    public function deleteFromImapAction(Request $request, Ticket $ticket, Client $client): Response
    {
        return $this->handleTicketDeleteFromImap($request, $ticket, $client);
    }

    /**
     * @Route(
     *     "/{clientId}/tickets/{ticketId}/status-edit/{status}",
     *     name="client_ticket_status_edit",
     *     requirements={"clientId": "\d+", "ticketId": "\d+", "status": "\d+"}
     * )
     * @Method("GET")
     * @ParamConverter("ticket", options={"id" = "ticketId"})
     * @ParamConverter("client", options={"id" = "clientId"})
     * @CsrfToken()
     * @Permission("view")
     */
    public function editTicketStatusAction(Request $request, Ticket $ticket, Client $client, int $status): Response
    {
        $this->denyAccessUnlessPermissionGranted(Permission::EDIT, TicketController::class);

        return $this->handleTicketStatusEdit($request, $ticket, $status, $client);
    }

    /**
     * @Route(
     *     "/{clientId}/tickets/{ticketId}/ticket-group-edit/{ticketGroupId}",
     *     name="client_ticket_group_edit",
     *     requirements={"clientId": "\d+", "ticketId": "\d+", "ticketGroupId": "\d+"},
     *     defaults={"ticketGroupId": null}
     * )
     * @Method("GET")
     * @Permission("view")
     * @CsrfToken()
     * @ParamConverter("client", options={"id" = "clientId"})
     * @ParamConverter("ticket", options={"id" = "ticketId"})
     * @ParamConverter("ticketGroup", options={"id" = "ticketGroupId"})
     */
    public function editTicketGroupAction(
        Request $request,
        Ticket $ticket,
        Client $client,
        ?TicketGroup $ticketGroup = null
    ): Response {
        $this->denyAccessUnlessPermissionGranted(Permission::EDIT, TicketController::class);

        return $this->handleTicketGroupEdit($request, $ticket, $ticketGroup, $client);
    }

    /**
     * @Route(
     *     "/{clientId}/tickets/{ticketId}/job-add/{jobId}",
     *     name="client_ticket_job_add",
     *     requirements={"clientId": "\d+", "ticketId": "\d+", "jobId": "\d+"},
     * )
     * @Method("GET")
     * @Permission("view")
     * @CsrfToken()
     * @ParamConverter("client", options={"id" = "clientId"})
     * @ParamConverter("ticket", options={"id" = "ticketId"})
     * @ParamConverter("job", options={"id" = "jobId"})
     */
    public function addTicketJobAction(Request $request, Ticket $ticket, Client $client, Job $job): Response
    {
        $this->denyAccessUnlessPermissionGranted(Permission::EDIT, TicketController::class);

        return $this->handleTicketAddJob($request, $ticket, $job, $client);
    }

    /**
     * @Route(
     *     "/{clientId}/tickets/{ticketId}/job-remove/{jobId}",
     *     name="client_ticket_job_remove",
     *     requirements={"clientId": "\d+", "ticketId": "\d+", "jobId": "\d+"},
     * )
     * @Method("GET")
     * @Permission("view")
     * @CsrfToken()
     * @ParamConverter("client", options={"id" = "clientId"})
     * @ParamConverter("ticket", options={"id" = "ticketId"})
     * @ParamConverter("job", options={"id" = "jobId"})
     */
    public function removeTicketJobAction(Request $request, Ticket $ticket, Client $client, Job $job): Response
    {
        $this->denyAccessUnlessPermissionGranted(Permission::EDIT, TicketController::class);

        return $this->handleTicketRemoveJob($request, $ticket, $job, $client);
    }

    /**
     * @Route(
     *     "/{clientId}/ticket/{ticketId}/job-create",
     *     name="client_ticket_job_create",
     *     requirements={"clientId": "\d+", "ticketId": "\d+"}
     * )
     * @Method({"GET", "POST"})
     * @Permission("edit")
     * @ParamConverter("ticket", options={"id" = "ticketId"})
     * @ParamConverter("client", options={"id" = "clientId"})
     */
    public function ticketCreateJobAction(Request $request, Ticket $ticket, Client $client): Response
    {
        return $this->handleTicketNewJob($request, $ticket, $client);
    }

    /**
     * @Route(
     *     "/{clientId}/tickets/{ticketId}/assign",
     *     name="client_ticket_assign",
     *     requirements={"ticketId": "\d+", "clientId": "\d+"}
     * )
     * @Method({"GET", "POST"})
     * @ParamConverter("ticket", options={"id" = "ticketId"})
     * @ParamConverter("client", options={"id" = "clientId"})
     * @Permission("view")
     */
    public function assignTicketAction(Request $request, Ticket $ticket, Client $client): Response
    {
        $this->denyAccessUnlessPermissionGranted(Permission::EDIT, TicketController::class);

        return $this->handleTicketAssign($request, $ticket, $client);
    }

    /**
     * @Route("/billing/payments/{id}/delete", name="client_payment_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("view")
     */
    public function deletePaymentAction(Payment $payment): RedirectResponse
    {
        $this->denyAccessUnlessPermissionGranted(Permission::EDIT, PaymentController::class);

        $client = $payment->getClient();

        if ($this->get(PaymentFacade::class)->handleDelete($payment)) {
            $this->addTranslatedFlash('success', 'Payment has been deleted.');
        } else {
            $this->addTranslatedFlash('error', 'Payment with a refund cannot be deleted.');
        }

        return $this->redirect(
            $this->generateUrl(
                'client_show_payments',
                [
                    'id' => $client->getId(),
                ]
            )
        );
    }

    /**
     * @Route(
     *     "/{id}/billing/payments/new/{invoice}",
     *     name="client_payment_new",
     *     requirements={
     *         "id": "\d+",
     *         "invoice": "\d+"
     *     }
     * )
     * @ParamConverter("invoice", options={"id" = "invoice"})
     * @Method({"GET", "POST"})
     * @Permission("view")
     */
    public function addPaymentAction(Request $request, Client $client, Invoice $invoice = null): Response
    {
        if (! $this->isPermissionGranted(Permission::EDIT, PaymentController::class)) {
            $this->denyAccessUnlessPermissionGranted(SpecialPermission::ALLOWED, SpecialPermission::PAYMENT_CREATE);
        }

        if ($client->getIsLead()) {
            $this->addTranslatedFlash('error', 'This action is not possible, while the client is lead.');

            return $this->createAjaxResponse();
        }

        return $this->handleAddPaymentAction($request, $client, $invoice);
    }

    /**
     * @Route("/{id}/billing/refunds/new", name="client_refund_new", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("view")
     */
    public function addRefundAction(Request $request, Client $client): Response
    {
        $this->denyAccessUnlessPermissionGranted(Permission::EDIT, RefundController::class);

        if ($client->getIsLead()) {
            $this->addTranslatedFlash('error', 'This action is not possible, while the client is lead.');

            return $this->createAjaxResponse();
        }

        $refund = new Refund();
        $refund->setClient($client);
        $refund->setCurrency($client->getOrganization()->getCurrency());
        $refund->setCreatedDate(new \DateTime());

        $url = $this->generateUrl('client_refund_new', ['id' => $client->getId()]);
        $form = $this->createForm(RefundType::class, $refund, ['action' => $url, 'client' => $client]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(RefundFacade::class)->handleCreate($refund);

            $this->addTranslatedFlash('success', 'Refund has been created.');

            return $this->createAjaxRedirectResponse(
                'client_show_refunds',
                [
                    'id' => $client->getId(),
                ]
            );
        }

        return $this->render(
            'refunds/components/add_form.html.twig',
            [
                'form' => $form->createView(),
                'client' => $client,
            ]
        );
    }

    /**
     * @Route("/billing/refunds/{id}/delete", name="client_refund_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("view")
     */
    public function deleteRefundAction(Refund $refund): RedirectResponse
    {
        $this->denyAccessUnlessPermissionGranted(Permission::EDIT, RefundController::class);

        $clientId = $refund->getClient()->getId();
        if (! $this->get(RefundFacade::class)->handleDelete($refund)) {
            $this->addTranslatedFlash('error', 'Refund could not be deleted.');

            return $this->redirectToRoute(
                'client_show_refunds',
                [
                    'id' => $clientId,
                ]
            );
        }

        $this->addTranslatedFlash('success', 'Refund has been deleted.');

        return $this->redirectToRoute(
            'client_show_refunds',
            [
                'id' => $clientId,
            ]
        );
    }

    /**
     * @Route("/{id}/edit", name="client_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, Client $client): Response
    {
        $clientDataProvider = $this->get(ClientDataProvider::class);
        $hasFinancialEntities = $clientDataProvider->hasRelationToFinancialEntities($client);
        $canBeConvertedToLead = $client->getIsLead() || $clientDataProvider->canBeConvertedToLead($client);

        $this->get(ClientFactory::class)->addDefaultContactIfNeeded($client);

        $multipleTaxes = $this->getOption(Option::PRICING_MULTIPLE_TAXES);
        $organizationCount = $this->em->getRepository(Organization::class)->getCount();

        $clientBeforeUpdate = clone $client;

        $form = $this->createForm(
            ClientType::class,
            $client,
            [
                'multipleTaxes' => $multipleTaxes,
                'firstClient' => false,
                'hasFinancialEntities' => $hasFinancialEntities,
                'disabledLeadChoice' => ! $canBeConvertedToLead,
                'includeOrganizationSelect' => $organizationCount !== 1,
                'attr' => [
                    'autocomplete' => 'off',
                ],
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (
                $hasFinancialEntities
                && $clientBeforeUpdate->getOrganization()->getCurrency() !== $client->getOrganization()->getCurrency()
            ) {
                $form->get('organization')->addError(
                    new FormError('Organization cannot be changed.')
                );
            }

            if ($form->isValid()) {
                if ($client->isDeleted()) {
                    $this->addTranslatedFlash(
                        'danger',
                        'Client is archived. All actions are prohibited. You can only restore the client.'
                    );
                } else {
                    $requestUser = $request->request->get('client')['user'];
                    if (array_key_exists('plainPassword', $requestUser)) {
                        $client->getUser()->setPlainPassword($requestUser['plainPassword']);
                    }

                    $this->get(ClientFacade::class)->handleUpdate($client, $clientBeforeUpdate);

                    $this->addTranslatedFlash('success', 'Client has been saved.');
                }

                return $this->redirectToRoute('client_show', ['id' => $client->getId()]);
            }
        }

        return $this->render(
            'client/edit.html.twig',
            [
                'client' => $client,
                'invoiceMaturityDays' => $client->getOrganization()->getInvoiceMaturityDays(),
                'stopServiceDue' => $this->getOption(Option::STOP_SERVICE_DUE),
                'stopServiceDueDays' => $this->getOption(Option::STOP_SERVICE_DUE_DAYS),
                'lateFeeDelayDays' => $this->getOption(Option::LATE_FEE_DELAY_DAYS),
                'sendInvoiceByPost' => $this->getOption(Option::SEND_INVOICE_BY_POST),
                'form' => $form->createView(),
                'multipleTaxes' => $multipleTaxes,
                'generateProformaInvoices' => $this->getOption(Option::GENERATE_PROFORMA_INVOICES),
            ]
        );
    }

    /**
     * @Route("/{id}/archive", name="client_archive", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function archiveAction(Client $client): RedirectResponse
    {
        if (Helpers::isDemo() && $client->getUser()->getUsername() === ClientFacade::DEMO_CLIENT_USERNAME) {
            $this->addTranslatedFlash('error', 'This client cannot be deleted in demo.');

            return $this->redirectToRoute(
                'client_show',
                [
                    'id' => $client->getId(),
                ]
            );
        }

        $this->get(ClientFacade::class)->handleArchive($client);

        $this->addTranslatedFlash(
            'success',
            'Client has been archived. All services were set as inactive. You may want to create new invoice.'
        );

        return $this->redirectToRoute('client_index');
    }

    /**
     * @Route("/{id}/delete-permanently", name="client_permanent_delete", requirements={"id": "\d+"})
     * @CsrfToken()
     * @Method("GET")
     * @Permission("edit")
     */
    public function deletePermanentlyAction(int $id): RedirectResponse
    {
        $client = $this->em->getRepository(Client::class)->find($id);

        if (! $client) {
            throw $this->createNotFoundException();
        }

        $clientId = $client->getId();

        try {
            $this->get(ClientFacade::class)->handleDelete($client);
        } catch (CannotDeleteDemoClientException $exception) {
            $this->addTranslatedFlash('error', 'This client cannot be deleted in demo.');

            return $this->redirectToRoute(
                'client_show',
                [
                    'id' => $clientId,
                ]
            );
        } catch (CannotCancelClientSubscriptionException $exception) {
            $this->addTranslatedFlash(
                'error',
                'Failed to cancel subscription "%subscription%".',
                null,
                [
                    '%subscription%' => $exception->getPaymentPlan()->getName(),
                ]
            );

            return $this->redirectToRoute(
                'client_show',
                [
                    'id' => $clientId,
                ]
            );
        }

        $this->addTranslatedFlash(
            'success',
            'Client has been permanently deleted.'
        );

        return $this->redirectToRoute('client_index');
    }

    /**
     * @Route("/{id}/restore", name="client_restore", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function restoreAction(int $id): RedirectResponse
    {
        $client = $this->em->find(Client::class, $id);
        if (! $client) {
            throw $this->createNotFoundException();
        }

        $client->setDeletedAt(null);
        $this->em->flush();

        $this->addTranslatedFlash('success', 'Client has been restored.');

        return $this->redirectToRoute('client_index');
    }

    /**
     * @Route("/{id}/convert-lead", name="client_convert_lead", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function convertLeadAction(Client $client): RedirectResponse
    {
        $this->notDeleted($client);

        $clientBeforeUpdate = clone $client;
        $client->setIsLead(false);
        $this->get(ClientFacade::class)->handleUpdate($client, $clientBeforeUpdate);

        $this->addTranslatedFlash('success', 'Client lead has been converted to regular client.');

        return $this->redirectToRoute(
            'client_show',
            [
                'id' => $client->getId(),
            ]
        );
    }

    /**
     * @Route(
     *     "/{id}/client-tag/{clientTag}/add",
     *     name="client_client_tag_add",
     *     requirements={"id": "\d+", "clientTag": "\d+"}
     * )
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function addClientTagAction(Client $client, ClientTag $clientTag): Response
    {
        $clientBeforeUpdate = clone $client;
        if (! $client->getClientTags()->contains($clientTag)) {
            $client->addClientTag($clientTag);
            $this->get(ClientFacade::class)->handleUpdate($client, $clientBeforeUpdate);
        }

        $this->invalidateTemplate(
            'client-tags',
            'client/components/view/client_tags.html.twig',
            [
                'client' => $client,
                'clientTags' => $this->get(ClientTagDataProvider::class)->getAllPossibleTagsForClient($client),
            ]
        );

        $this->addTranslatedFlash('success', 'Client tag has been added.');

        return $this->createAjaxResponse();
    }

    /**
     * @Route(
     *     "/{id}/client-tag/{clientTag}/delete",
     *     name="client_client_tag_delete",
     *     requirements={"id": "\d+", "clientTag": "\d+"}
     * )
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteClientTagAction(Client $client, ClientTag $clientTag): Response
    {
        $clientBeforeUpdate = clone $client;
        $client->removeClientTag($clientTag);
        $this->get(ClientFacade::class)->handleUpdate($client, $clientBeforeUpdate);

        $this->invalidateTemplate(
            'client-tags',
            'client/components/view/client_tags.html.twig',
            [
                'client' => $client,
                'clientTags' => $this->get(ClientTagDataProvider::class)->getAllPossibleTagsForClient($client),
            ]
        );

        $this->addTranslatedFlash('success', 'Client tag has been deleted.');

        return $this->createAjaxResponse();
    }

    /**
     * @Route("/{id}/bank-account/new", name="client_bank_account_number_add", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function addClientBankAccountNumberAction(Request $request, Client $client): Response
    {
        $bankAccount = new ClientBankAccount();
        $bankAccount->setClient($client);

        $url = $this->generateUrl('client_bank_account_number_add', ['id' => $client->getId()]);
        $form = $this->createForm(ClientBankAccountType::class, $bankAccount, ['action' => $url]);
        $form->handleRequest($request);

        $hasStripeAch = $client->getOrganization()->hasStripeAch($this->isSandbox());

        if ($form->isSubmitted() && $form->isValid()) {
            if ($client->isDeleted()) {
                $this->addTranslatedFlash(
                    'danger',
                    'Client is archived. All actions are prohibited. You can only restore the client.'
                );

                return $this->createAjaxRedirectResponse(
                    'client_show',
                    [
                        'id' => $client->getId(),
                    ]
                );
            }

            $this->em->persist($bankAccount);
            $this->em->flush();

            $this->invalidateTemplate(
                'client-bank-accounts',
                'client/components/view/bank_accounts.html.twig',
                [
                    'client' => $client,
                    'hasStripeAch' => $hasStripeAch,
                ]
            );

            $this->invalidateTemplate(
                'stripe-ach',
                'client/components/view/stripe_ach.html.twig',
                [
                    'client' => $client,
                    'hasStripeAch' => $hasStripeAch,
                ]
            );

            $this->addTranslatedFlash('success', 'Bank account has been created.');

            return $this->createAjaxResponse();
        }

        return $this->render(
            'client/components/edit/bank_account.html.twig',
            [
                'form' => $form->createView(),
                'hasStripeAch' => $hasStripeAch,
            ]
        );
    }

    /**
     * @Route("/log/edit/{id}", name="client_log_edit", requirements={"id": "\d+"})
     * @Method({"GET","POST"})
     * @Permission("view")
     */
    public function editClientLogAction(Request $request, ClientLog $clientLog): Response
    {
        $this->denyAccessUnlessPermissionGranted(SpecialPermission::ALLOWED, SpecialPermission::CLIENT_LOG_EDIT);
        $urlParams = ['id' => $clientLog->getId()];
        if ($logTypeFilters = $request->get('logType')) {
            $urlParams['logType'] = $logTypeFilters;
        }
        $url = $this->generateUrl('client_log_edit', $urlParams);
        $form = $this->createForm(ClientLogType::class, $clientLog, ['action' => $url]);
        $form->handleRequest($request);

        $client = $clientLog->getClient();

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $logTypeFiltersQuery = $request->get('logType', ClientLogsView::LOG_TYPES_ARRAY);

            $this->invalidateTemplate(
                'client-log',
                'client/components/view/client_log.html.twig',
                [
                    'client' => $client,
                    'clientLogGrid' => $this->get(ClientLogsViewGridFactory::class)->create(
                        $client,
                        ['logType' => $logTypeFiltersQuery]
                    ),
                    'clientLogFiltersLink' => $this->createClientLogFiltersLinks($logTypeFiltersQuery),
                    'clientLogTypeFilters' => $logTypeFiltersQuery,
                    'newLogForm' => $this->createAddClientLogForm($request, $client)->createView(),
                    'logExportForm' => $this->createLogExportForm($request, $client)->createView(),
                ]
            );

            $this->invalidateTemplate(
                'stripe-ach',
                'client/components/view/stripe_ach.html.twig',
                [
                    'client' => $client,
                    'hasStripeAch' => $client->getOrganization()->hasStripeAch($this->isSandbox()),
                ]
            );

            $this->addTranslatedFlash('success', 'Client log has been edited.');

            return $this->createAjaxResponse();
        }

        return $this->render(
            'client/components/edit/client_log.html.twig',
            [
                'form' => $form->createView(),
                'isEdit' => true,
            ]
        );
    }

    /**
     * @Route("/log/delete/{id}", name="client_log_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("view")
     */
    public function deleteClientLogAction(Request $request, ClientLog $clientLog): Response
    {
        $this->denyAccessUnlessPermissionGranted(SpecialPermission::ALLOWED, SpecialPermission::CLIENT_LOG_EDIT);

        $client = $clientLog->getClient();

        $this->get(ClientLogFacade::class)->handleDelete($clientLog);

        $logTypeFiltersQuery = $request->get('logType', ClientLogsView::LOG_TYPES_ARRAY);

        $this->invalidateTemplate(
            'client-log',
            'client/components/view/client_log.html.twig',
            [
                'client' => $client,
                'clientLogGrid' => $this->get(ClientLogsViewGridFactory::class)->create(
                    $client,
                    ['logType' => $logTypeFiltersQuery]
                ),
                'clientLogFiltersLink' => $this->createClientLogFiltersLinks($logTypeFiltersQuery),
                'clientLogTypeFilters' => $logTypeFiltersQuery,
                'newLogForm' => $this->createAddClientLogForm($request, $client)->createView(),
                'logExportForm' => $this->createLogExportForm($request, $client)->createView(),
            ]
        );

        $this->addTranslatedFlash('success', 'Client log has been deleted.');

        return $this->createAjaxResponse();
    }

    /**
     * @Route("/log/new-ticket/{id}", name="client_log_new_ticket", requirements={"id": "\d+"})
     * @Method({"GET","POST"})
     * @Permission("view")
     */
    public function addTicketFromClientLogAction(Request $request, ClientLog $clientLog)
    {
        $this->denyAccessUnlessPermissionGranted(Permission::EDIT, TicketController::class);

        $url = $this->generateUrl(
            'client_log_new_ticket',
            [
                'id' => $clientLog->getId(),
            ]
        );
        $data = new TicketNewUserData();
        $data->client = $clientLog->getClient();
        $data->message = $clientLog->getMessage();
        $form = $this->createForm(
            TicketUserType::class,
            $data,
            [
                'action' => $url,
                'show_client_select' => false,
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(TicketFacade::class)->handleNewFromUserData($data, $this->getUser());
            $this->addTranslatedFlash('success', 'Ticket has been created.');
            foreach ($this->get(ExceptionStash::class)->getAll() as $message => $exception) {
                if ($exception instanceof FlashMessageExceptionInterface) {
                    $this->addTranslatedFlash('error', $message, null, $exception->getParameters());
                } else {
                    $this->addTranslatedFlash('error', $message);
                }
            }

            return $this->createAjaxRedirectResponse(
                'client_show_tickets',
                [
                    'clientId' => $clientLog->getClient()->getId(),
                ]
            );
        }

        return $this->render(
            'client/tickets/new_ticket_modal.html.twig',
            [
                'form' => $form->createView(),
                'ticketingRoutesMap' => $this->getTicketingRoutesMap(),
                'hasContactEmail' => (bool) $clientLog->getClient()->getContactEmails(),
                'notificationTicketUserCreatedByEmail' => $this->getOption(
                    Option::NOTIFICATION_TICKET_USER_CREATED_BY_EMAIL
                ),
            ]
        );
    }

    /**
     * @Route("/bank-account/{id}/delete", name="client_bank_account_remove", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteClientBankAccountNumberAction(ClientBankAccount $clientBankAccount): Response
    {
        $client = $clientBankAccount->getClient();

        if ($client->isDeleted()) {
            $this->addTranslatedFlash(
                'danger',
                'Client is archived. All actions are prohibited. You can only restore the client.'
            );

            return $this->createAjaxRedirectResponse(
                'client_show',
                [
                    'id' => $client->getId(),
                ]
            );
        }

        $this->get(ClientBankAccountFacade::class)->handleDelete($clientBankAccount);

        $hasStripeAch = $client->getOrganization()->hasStripeAch($this->isSandbox());

        $this->invalidateTemplate(
            'client-bank-accounts',
            'client/components/view/bank_accounts.html.twig',
            [
                'client' => $client,
                'hasStripeAch' => $hasStripeAch,
            ]
        );

        $this->invalidateTemplate(
            'stripe-ach',
            'client/components/view/stripe_ach.html.twig',
            [
                'client' => $client,
                'hasStripeAch' => $hasStripeAch,
            ]
        );

        $this->addTranslatedFlash('success', 'Bank account has been deleted.');

        return $this->createAjaxResponse();
    }

    /**
     * @Route("/bank-account/{id}/edit", name="client_bank_account_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editClientBankAccountNumberAction(Request $request, ClientBankAccount $clientBankAccount): Response
    {
        $url = $this->generateUrl('client_bank_account_edit', ['id' => $clientBankAccount->getId()]);
        $form = $this->createForm(ClientBankAccountType::class, $clientBankAccount, ['action' => $url]);
        $form->handleRequest($request);

        $hasStripeAch = $clientBankAccount->getClient()->getOrganization()->hasStripeAch($this->isSandbox());

        if ($form->isSubmitted() && $form->isValid()) {
            $client = $clientBankAccount->getClient();

            if ($client->isDeleted()) {
                $this->addTranslatedFlash(
                    'danger',
                    'Client is archived. All actions are prohibited. You can only restore the client.'
                );

                return $this->createAjaxRedirectResponse(
                    'client_show',
                    [
                        'id' => $client->getId(),
                    ]
                );
            }

            $this->em->flush();

            $this->invalidateTemplate(
                'client-bank-accounts',
                'client/components/view/bank_accounts.html.twig',
                [
                    'client' => $client,
                    'hasStripeAch' => $hasStripeAch,
                ]
            );

            $this->invalidateTemplate(
                'stripe-ach',
                'client/components/view/stripe_ach.html.twig',
                [
                    'client' => $client,
                    'hasStripeAch' => $hasStripeAch,
                ]
            );

            $this->addTranslatedFlash('success', 'Bank account has been saved.');

            return $this->createAjaxResponse();
        }

        return $this->render(
            'client/components/edit/bank_account.html.twig',
            [
                'form' => $form->createView(),
                'hasStripeAch' => $hasStripeAch,
            ]
        );
    }

    /**
     * @Route("/bank-account/{id}/stripe-add-bank-account-token", name="client_bank_account_stripe_create_token", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("edit")
     * @CsrfToken()
     */
    public function addStripeBankAccountToken(ClientBankAccount $bankAccount): Response
    {
        try {
            $this->get(ClientBankAccountFacade::class)->createStripeBankAccount($bankAccount, $this->isSandbox());
            $this->addTranslatedFlash('success', 'Bank account has been connected.');
        } catch (Error\Permission | Error\Authentication | Error\InvalidRequest | \InvalidArgumentException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());
        }

        $this->invalidateTemplate(
            'stripe-ach',
            'client/components/view/stripe_ach.html.twig',
            [
                'client' => $bankAccount->getClient(),
                'hasStripeAch' => $bankAccount->getClient()->getOrganization()->hasStripeAch($this->isSandbox()),
            ]
        );

        return $this->createAjaxResponse();
    }

    /**
     * @Route("/bank-account/{id}/stripe-bank-account-verify", name="client_bank_account_stripe_verify", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function verifyStripeBankAccount(ClientBankAccount $bankAccount, Request $request): Response
    {
        $data = new StripeAchVerifyData();
        $url = $this->generateUrl('client_bank_account_stripe_verify', ['id' => $bankAccount->getId()]);
        $form = $this->createForm(StripeAchVerifyType::class, $data, ['action' => $url]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $isStripeError = false;
            try {
                $this->get(ClientBankAccountFacade::class)->verifyStripeBankAccount(
                    $bankAccount,
                    $this->isSandbox(),
                    $data->firstDeposit,
                    $data->secondDeposit
                );
            } catch (Error\Permission | Error\Authentication | Error\InvalidRequest $exception) {
                $isStripeError = true;
                $this->addTranslatedFlash('error', $exception->getMessage());
            } catch (Error\Card $exception) {
                $form->get('firstDeposit')->addError(
                    new FormError(
                        'The values provided do not match the values of the two micro-deposits that were sent. Or the limit of verification attempts was exceeded (max. 10 failed verification attempts).'
                    )
                );
            }

            if (! $isStripeError && $form->isValid()) {
                $this->addTranslatedFlash('success', 'Bank account has been verified');
            }

            if ($isStripeError || $form->isValid()) {
                $this->invalidateTemplate(
                    'stripe-ach',
                    'client/components/view/stripe_ach.html.twig',
                    [
                        'client' => $bankAccount->getClient(),
                        'hasStripeAch' => $bankAccount->getClient()->getOrganization()->hasStripeAch(
                            $this->isSandbox()
                        ),
                    ]
                );

                return $this->createAjaxResponse();
            }
        }

        return $this->render(
            'client/components/view/stripe_ach_verify_modal.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/fee/{id}/delete", name="client_fee_remove", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteClientFeeAction(Fee $fee): Response
    {
        $client = $fee->getClient();

        if ($fee->isInvoiced()) {
            $this->addTranslatedFlash(
                'warning',
                'Fee is already invoiced and can not be deleted. It is no longer listed in late fees.'
            );
        } elseif (! $fee->getQuoteItems()->isEmpty()) {
            $this->addTranslatedFlash(
                'error',
                'Fee can not be deleted, because it\'s present on a quote.'
            );
        } else {
            try {
                $this->em->remove($fee);
                $this->em->flush();
            } catch (ForeignKeyConstraintViolationException $exception) {
                $this->addTranslatedFlash(
                    'error',
                    'Fee can not be deleted, because it\'s present on an invoice.'
                );

                return $this->createAjaxResponse();
            }

            $this->em->refresh($client);

            $this->addTranslatedFlash('success', 'Fee has been deleted.');
        }

        $fees = $this->em->getRepository(Fee::class)->getClientUninvoicedFees($client);

        $this->invalidateTemplate(
            'client-fees',
            'client/components/view/fees.html.twig',
            [
                'client' => $client,
                'fees' => $fees,
                'showFeeWarning' => ! Invoicing::isLikelyToHaveFutureInvoice(
                    $client,
                    $this->getOption(Option::STOP_INVOICING)
                ),
            ]
        );

        return $this->createAjaxResponse();
    }

    /**
     * @Route("/{id}/send-invitation-email", name="client_send_invitation_email", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("view")
     */
    public function sendInvitationEmailAction(Client $client): RedirectResponse
    {
        if ($client->getIsLead()) {
            $this->addTranslatedFlash('error', 'This action is not possible, while the client is lead.');

            return $this->redirectToRoute('client_show', ['id' => $client->getId()]);
        }

        try {
            $this->get(InvitationEmailSender::class)->send($client);
        } catch (PublicUrlGeneratorException | NoClientContactException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());

            return $this->redirectToRoute(
                'client_show',
                [
                    'id' => $client->getId(),
                ]
            );
        }

        $this->addTranslatedFlash('success', 'Invitation email has been sent.');

        return $this->redirectToRoute('client_show', ['id' => $client->getId()]);
    }

    /**
     * @Route("/{id}/send-reset-password-link", name="client_send_reset_password_link", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("view")
     */
    public function sendResetPasswordLinkAction(Client $client): RedirectResponse
    {
        if ($client->getIsLead()) {
            $this->addTranslatedFlash('error', 'This action is not possible, while the client is lead.');

            return $this->redirectToRoute('client_show', ['id' => $client->getId()]);
        }

        $user = $client->getUser();

        $this->get(UserFacade::class)->handleRequestPasswordReset($user);

        try {
            $this->container->get(ResetPasswordEmailSender::class)->sendResettingEmailMessage($user);
        } catch (PublicUrlGeneratorException | NoClientContactException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());

            return $this->redirectToRoute(
                'client_show',
                [
                    'id' => $client->getId(),
                ]
            );
        }

        $contactEmails = $client->getContactEmails();
        $message['logMsg'] = [
            'message' => 'Admin has sent a password reset link for client %s.',
            'replacements' => sprintf('ID %s (%s)', $client->getId(), implode(', ', $contactEmails)),
        ];

        $this->get(ActionLogger::class)->log($message, $user, $user->getClient(), EntityLog::PASSWORD_CHANGE);

        $this->addTranslatedFlash('success', 'Reset password email has been added to the send queue.');

        return $this->redirectToRoute('client_show', ['id' => $client->getId()]);
    }

    /**
     * @Route("/send-invitation-emails", name="client_send_invitation_emails")
     * @Method("GET")
     * @CsrfToken()
     * @Permission("view")
     */
    public function sendInvitationEmailsAction(): RedirectResponse
    {
        if (Helpers::isDemo()) {
            $this->addTranslatedFlash('error', 'This feature is not available in the demo.');

            return $this->redirectToRoute('client_index');
        }

        $this->get(RabbitMqEnqueuer::class)->enqueue(
            new SendInvitationEmailsMessage()
        );

        $this->addTranslatedFlash('success', 'Invitation emails have been sent.');

        $message['logMsg'] = [
            'message' => 'Invitation emails requested.',
            'replacements' => '',
        ];
        $logger = $this->container->get(ActionLogger::class);
        $logger->log($message, $this->getUser(), null, EntityLog::SEND_INVITATIONS);

        return $this->redirectToRoute('mailing_index');
    }

    /**
     * @Route("/get-next-id/{id}", name="client_next_id", options={"expose": true}, requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function getNextClientIdAction(Organization $organization): JsonResponse
    {
        return new JsonResponse(
            [
                'id' => $this->em->getRepository(Client::class)->getNextClientCustomId($organization),
            ]
        );
    }

    /**
     * @Route("/{id}/enter-client-zone", name="client_enter_client_zone")
     * @Method("GET")
     * @CsrfToken()
     * @Permission("view")
     */
    public function enterClientZoneAction(int $id): RedirectResponse
    {
        $client = $this->em->find(Client::class, $id);

        $this->denyAccessUnlessPermissionGranted(SpecialPermission::ALLOWED, SpecialPermission::CLIENT_IMPERSONATION);

        if (! $client) {
            throw $this->createNotFoundException('Client not found');
        }

        if ($client->isDeleted()) {
            $this->addTranslatedFlash(
                'error',
                'Client is archived. All actions are prohibited. You can only restore the client.'
            );

            return $this->redirectToRoute(
                'client_show',
                [
                    'id' => $client->getId(),
                ]
            );
        }

        if (! $client->getUser()->getUsername()) {
            $this->addTranslatedFlash(
                'error',
                'Client does not have username. Fill the username to enable client zone.'
            );

            return $this->redirectToRoute(
                'client_show',
                [
                    'id' => $client->getId(),
                ]
            );
        }

        return $this->redirectToRoute(
            'client_zone_client_index',
            [
                '_client_impersonation' => $client->getUser()->getUsername(),
            ]
        );
    }

    /**
     * @Route("/{client}/resend/{id}", name="client_log_email_resend")
     * @Method("GET")
     * @Permission("edit")
     * @CsrfToken()
     */
    public function resendAction(Client $client, EmailLog $emailLog): Response
    {
        if (empty(trim($emailLog->getRecipient()))) {
            $this->addTranslatedFlash('error', 'Email could not be sent, because recipient is empty.');
        } else {
            try {
                $this->get(EmailLogFacade::class)->resendEmail($emailLog);
                $this->addTranslatedFlash('success', 'Email has been added to the send queue.');
            } catch (EmailAttachmentNotFoundException $exception) {
                $this->addTranslatedFlash(
                    'error',
                    'Email can\'t be resent because the original attachment is missing.'
                );
            } catch (\Exception $exception) {
                $this->addTranslatedFlash('error', 'Email could not be added to the send queue!');
                $this->addTranslatedFlash('warning', $exception->getMessage());
            }
        }

        return $this->redirectToRoute('client_show', ['id' => $client->getId()]);
    }

    /**
     * @Route("/contact-type-collection-update", name="client_contact_type_collection_update", options={"expose"=true})
     * @Method("GET")
     * @Permission("view")
     */
    public function getContactTypeCollectionUpdate(): JsonResponse
    {
        $client = new Client();
        $form = $this->createForm(ClientType::class, $client)->createView();

        return new JsonResponse(
            [
                'prototype' => $this->renderView(
                    'client/components/edit/contact_collection_item_prototype.html.twig',
                    [
                        'form' => $form,
                    ]
                ),
            ]
        );
    }

    public function getTicketingRoutesMap(): TicketingRoutesMap
    {
        if (! $this->ticketingRoutesMap) {
            $map = new TicketingRoutesMap();
            $map->view = 'client_show_tickets';
            $map->delete = 'client_ticket_delete';
            $map->deleteFromImap = 'client_ticket_delete_from_imap';
            $map->statusEdit = 'client_ticket_status_edit';
            $map->ticketGroupEdit = 'client_ticket_group_edit';
            $map->assign = 'client_ticket_assign';
            $map->jobAdd = 'client_ticket_job_add';
            $map->jobRemove = 'client_ticket_job_remove';
            $map->jobCreate = 'client_ticket_job_create';

            $this->ticketingRoutesMap = $map;
        }

        return $this->ticketingRoutesMap;
    }

    public function getInvoiceRoutesMap(): InvoiceRoutesMap
    {
        if (! $this->invoiceRoutesMap) {
            $map = new InvoiceRoutesMap();
            $map->paymentNew = 'client_payment_new';
            $map->show = 'client_invoice_show';

            $this->invoiceRoutesMap = $map;
        }

        return $this->invoiceRoutesMap;
    }

    private function createAddClientLogForm(Request $request, Client $client): FormInterface
    {
        $clientLog = new ClientLog();
        $clientLog->setClient($client);
        $clientLog->setCreatedDate(new \DateTime());
        $clientLog->setUser($this->getUser());
        $urlParams = ['id' => $client->getId()];
        if ($logTypeFilters = $request->get('logType')) {
            $urlParams['logType'] = $logTypeFilters;
        }
        $url = $this->generateUrl('client_show', $urlParams);

        return $this->createForm(ClientLogType::class, $clientLog, ['action' => $url]);
    }

    private function handleAddClientLogForm(
        Request $request,
        Client $client,
        FormInterface $form,
        array $templateParameters
    ): ?Response {
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->denyAccessUnlessPermissionGranted(Permission::VIEW, self::class);

            $this->em->persist($form->getData());
            $this->em->flush();

            $this->invalidateTemplate(
                'client-log',
                'client/components/view/client_log.html.twig',
                array_merge(
                    $templateParameters,
                    [
                        'newLogForm' => $this->createAddClientLogForm($request, $client)->createView(),
                        'logExportForm' => $this->createLogExportForm($request, $client)->createView(),
                        'client' => $client,
                    ]
                )
            );

            $this->addTranslatedFlash('success', 'Client log has been created.');

            return $this->createAjaxResponse();
        }

        return null;
    }

    private function createLogExportForm(Request $request, Client $client): FormInterface
    {
        $urlParams = ['id' => $client->getId()];
        if ($logTypeFilters = $request->get('logType')) {
            $urlParams['logType'] = $logTypeFilters;
        }
        $url = $this->generateUrl('client_show', $urlParams);

        return $this->createForm(ClientLogExportType::class, null, ['action' => $url]);
    }

    private function handleLogExportForm(
        Request $request,
        Client $client,
        array $filters,
        FormInterface $form
    ): ?Response {
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $from = $data['fromDate'];
            $to = $data['toDate'];

            if ($from instanceof \DateTime) {
                $from = $from->modify('midnight today');
            }

            if ($to instanceof \DateTime) {
                $to = $to->modify('midnight tomorrow');
            }

            $logs = $this->get(ClientLogsViewDataProvider::class)->getByDate($client, $filters, $from, $to);

            if (! $logs) {
                $this->addTranslatedFlash('warning', 'There are no client logs to export.');
            } else {
                $ids = array_map(
                    function (ClientLogsView $log) {
                        return $log->getId();
                    },
                    $logs
                );
                /** @var SubmitButton $pdfButton */
                $pdfButton = $form->get('pdfButton');
                $fileType = $pdfButton->isClicked() ? 'PDF' : 'CSV';
                $count = count($ids);
                $this->get(ClientLogsViewFacade::class)->prepareExport(
                    $this->transChoice(
                        '%filetype% overview of %count% client logs',
                        $count,
                        [
                            '%count%' => $count,
                            '%filetype%' => $fileType,
                        ]
                    ),
                    $ids,
                    $this->getUser(),
                    $fileType === 'PDF'
                        ? ExportClientLogsViewMessage::FORMAT_PDF
                        : ExportClientLogsViewMessage::FORMAT_CSV
                );

                $this->addTranslatedFlash(
                    'success',
                    'Export was added to queue. You can download it in System > Tools > Downloads.',
                    null,
                    [
                        '%link%' => $this->generateUrl('download_index'),
                    ]
                );
            }

            $urlParams = ['id' => $client->getId()];
            if ($logTypeFilters = $request->get('logType')) {
                $urlParams['logType'] = $logTypeFilters;
            }

            return $this->redirectToRoute('client_show', $urlParams);
        }

        return null;
    }

    private function handleNoteForm(Request $request, Client $client, FormInterface $noteForm): ?Response
    {
        $clientBeforeUpdate = clone $client;
        $noteForm->handleRequest($request);

        if ($noteForm->isSubmitted() && $noteForm->isValid()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);

            if ($client->isDeleted()) {
                $this->addTranslatedFlash(
                    'danger',
                    'Client is archived. All actions are prohibited. You can only restore the client.'
                );
            } else {
                $this->get(ClientFacade::class)->handleUpdate($client, $clientBeforeUpdate);
                $this->addTranslatedFlash('success', 'Note has been saved.');
            }

            if ($request->isXmlHttpRequest()) {
                $this->invalidateTemplate(
                    'client-overview__note',
                    'client/components/view/notes.html.twig',
                    [
                        'client' => $client,
                        'noteForm' => $this->createForm(ClientNoteType::class, $client)->createView(),
                    ]
                );

                return $this->createAjaxResponse();
            }

            return $this->redirectToRoute(
                'client_show',
                [
                    'id' => $client->getId(),
                ]
            );
        }

        return null;
    }

    private function createClientLogFiltersLinks(array $logTypeFiltersQuery): array
    {
        $clientLogFiltersLink = [];
        foreach (ClientLogsView::LOG_TYPES_ARRAY as $identifier) {
            $filter = array_fill_keys($logTypeFiltersQuery, true);
            if (array_key_exists($identifier, $filter)) {
                unset($filter[$identifier]);
            } else {
                $filter[$identifier] = true;
            }
            $clientLogFiltersLink[$identifier] = array_keys($filter);
        }

        return $clientLogFiltersLink;
    }
}
