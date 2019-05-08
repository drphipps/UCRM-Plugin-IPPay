<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Financial\FinancialEmailSender;
use AppBundle\DataProvider\InvoiceDataProvider;
use AppBundle\Entity\Client;
use AppBundle\Entity\EntityLog;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\NotificationTemplate;
use AppBundle\Entity\Option;
use AppBundle\Facade\InvoiceFacade;
use AppBundle\Form\ChooseClientType;
use AppBundle\Form\SendInvoicesType;
use AppBundle\Grid\Invoice\InvoiceGridFactory;
use AppBundle\RabbitMq\Invoice\InitializeDraftGenerationMessage;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Service\ActionLogger;
use AppBundle\Util\Helpers;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use RabbitMqBundle\RabbitMqEnqueuer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/billing")
 * @PermissionControllerName(InvoiceController::class)
 */
class BillingController extends BaseController
{
    /**
     * @Route("/invoices", name="billing_index")
     * @Method({"GET", "POST"})
     * @Permission("view")
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(InvoiceGridFactory::class)->createInvoiceGrid();
        if ($response = $grid->processMultiAction()) {
            return $response;
        }
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        $invoicesToSend = $this->em->getRepository(Invoice::class)->existInvoicesToSend(
            $this->getOption(Option::SEND_INVOICE_WITH_ZERO_BALANCE)
        );

        return $this->render(
            'billing/index.html.twig',
            [
                'grid' => $grid,
                'invoicesToSend' => $invoicesToSend,
                'showProformaInvoices' => $this->get(InvoiceDataProvider::class)->showProformaInvoices(),
            ]
        );
    }

    /**
     * @Route("/invoices/new", name="billing_add_invoice")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newInvoiceAction(Request $request): Response
    {
        $form = $this->createForm(ChooseClientType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Client $client */
            $client = $form->get('client')->getData();

            return $this->createAjaxRedirectResponse(
                'client_invoice_new',
                [
                    'id' => $client->getId(),
                ]
            );
        }

        return $this->render(
            'billing/add_invoice.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/invoices/generate-recurring", name="billing_invoice_generate_recurring_invoices")
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function generateRecurringInvoicesAction(): RedirectResponse
    {
        $this->get(RabbitMqEnqueuer::class)->enqueue(
            new InitializeDraftGenerationMessage(
                new \DateTimeImmutable(),
                false
            )
        );

        $this->addTranslatedFlash('success', 'Recurring invoice generation started.');

        if ($this->getOption(Option::SEND_INVOICE_BY_EMAIL)) {
            return $this->redirectToRoute('billing_index');
        }

        return $this->redirectToRoute('draft_index');
    }

    /**
     * @Route("/invoices/send", name="billing_invoice_send_invoices")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function sendInvoicesAction(Request $request): Response
    {
        $invoices = $this->em->getRepository(Invoice::class)->getInvoicesToSend(
            $this->getOption(Option::SEND_INVOICE_WITH_ZERO_BALANCE)
        );

        $defaultSendInvoiceByPost = $this->getOption(Option::SEND_INVOICE_BY_POST);

        $sendByPost = [];
        $checkboxes = [];
        foreach ($invoices as $invoice) {
            $checkboxes[$invoice->getId()] = (bool) $invoice->getClient()->getBillingEmails();

            if ($invoice->getClient()->getSendInvoiceByPost() ?? $defaultSendInvoiceByPost) {
                $sendByPost[] = $invoice->getId();
            }
        }

        $form = $this->createForm(
            SendInvoicesType::class,
            [
                'send' => $checkboxes,
            ],
            [
                'action' => $this->generateUrl('billing_invoice_send_invoices'),
            ]
        );
        $this->deserializeJsonForm($form->getName(), $request);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (Helpers::isDemo()) {
                $this->addTranslatedFlash('error', 'This feature is not available in the demo.');

                return $this->redirectToRoute('billing_index');
            }

            $invoiceIds = array_keys(array_filter($form->get('send')->getData()));

            $this->get(InvoiceFacade::class)->sendInvoiceEmails($invoiceIds);

            $message['logMsg'] = [
                'message' => 'Invoice emails sent.',
                'replacements' => '',
            ];
            $this->container->get(ActionLogger::class)->log($message, $this->getUser(), null, EntityLog::SEND_INVOICES);

            $sentInvoicesCount = count($invoiceIds);

            $invoices = $this->em->getRepository(Invoice::class)->getInvoicesToSend(
                $this->getOption(Option::SEND_INVOICE_WITH_ZERO_BALANCE)
            );

            foreach ($invoices as $key => $invoice) {
                if (
                    ! $invoice->getClient()->hasBillingEmail()
                    || in_array($invoice->getId(), $invoiceIds, true)
                ) {
                    unset($invoices[$key]);
                }
            }

            if ($invoices) {
                $sendByPost = [];
                $checkboxes = [];
                foreach ($invoices as $invoice) {
                    $checkboxes[$invoice->getId()] = (bool) $invoice->getClient()->getBillingEmails();

                    if ($invoice->getClient()->getSendInvoiceByPost() ?? $defaultSendInvoiceByPost) {
                        $sendByPost[] = $invoice->getId();
                    }
                }

                $form = $this->createForm(
                    SendInvoicesType::class,
                    [
                        'send' => $checkboxes,
                    ],
                    [
                        'action' => $this->generateUrl('billing_invoice_mark_as_sent_invoices'),
                    ]
                );

                return $this->render(
                    'billing/mark_invoices_sent.html.twig',
                    [
                        'invoices' => $invoices,
                        'sentInvoicesCount' => $sentInvoicesCount,
                        'form' => $form->createView(),
                    ]
                );
            }

            $this->addTranslatedFlash('success', 'Invoice emails have been sent.');

            return $this->createAjaxRedirectResponse('billing_index');
        }

        $invoicesWithoutEmail = [];
        foreach ($invoices as $key => $invoice) {
            if (! $invoice->getClient()->hasBillingEmail()) {
                $invoicesWithoutEmail[] = $invoice;
                unset($invoices[$key]);
            }
        }

        return $this->render(
            'billing/send_invoices.html.twig',
            [
                'invoices' => $invoices,
                'invoicesWithoutEmail' => $invoicesWithoutEmail,
                'sendByPost' => $sendByPost,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/invoices/send/{id}", name="billing_invoice_send_invoice")
     * @Method("GET")
     * @Permission("edit")
     * @CsrfToken()
     */
    public function sendInvoiceAction(Invoice $invoice): RedirectResponse
    {
        if ($invoice->getInvoiceStatus() === Invoice::DRAFT) {
            $this->addTranslatedFlash('error', 'Invoice drafts cannot be sent to client, they must be approved first.');
        } else {
            $this->get(FinancialEmailSender::class)->send(
                $invoice,
                $invoice->isProforma()
                    ? NotificationTemplate::CLIENT_NEW_PROFORMA_INVOICE
                    : NotificationTemplate::CLIENT_NEW_INVOICE
            );
            $this->addTranslatedFlash('success', 'Invoice has been queued for sending.');
        }

        return $this->redirectToRoute('billing_index');
    }

    /**
     * @Route("/invoices/mark-as-send", name="billing_invoice_mark_as_sent_invoices")
     * @Method("POST")
     * @Permission("edit")
     */
    public function markInvoicesAsSent(Request $request): JsonResponse
    {
        $invoices = $this->em->getRepository(Invoice::class)->getInvoicesToSend(
            $this->getOption(Option::SEND_INVOICE_WITH_ZERO_BALANCE)
        );

        $checkboxes = [];
        foreach ($invoices as $invoice) {
            $checkboxes[$invoice->getId()] = false;
        }

        $form = $this->createForm(
            SendInvoicesType::class,
            [
                'send' => $checkboxes,
            ]
        );

        $this->deserializeJsonForm($form->getName(), $request);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $invoiceIds = array_keys(array_filter($form->get('send')->getData()));

            $this->get(InvoiceFacade::class)->markAsSendInvoiceEmails($invoiceIds);

            $this->addTranslatedFlash('success', 'Invoices have been marked as sent.');
        }

        return $this->createAjaxRedirectResponse('billing_index');
    }

    /**
     * To exceed max_input_vars limit, we serialize each row into JSON.
     * The following code edits the request with unserialized info,
     * so that Symfony form can handle it and we can work normally.
     *
     * @todo investigate - maybe this could be made as some extension to forms
     * @todo either way, refactor this so it's not duplicated and try to generalize it
     *
     * @see https://ubnt.myjetbrains.com/youtrack/issue/UCRM-3105
     *
     * used in payment import as well:
     * @see PaymentImportController::deserializeJsonForm()
     */
    private function deserializeJsonForm(string $formName, Request $request): void
    {
        $formParameter = $request->request->get($formName);
        if (! $formParameter || ! array_key_exists('_jsonSerialized', $formParameter)) {
            return;
        }
        try {
            $formData = Json::decode($formParameter['_jsonSerialized']);
        } catch (JsonException $exception) {
            return;
        }

        foreach ($formData as $key => $value) {
            parse_str(sprintf('%s=%s', $key, $value), $result);

            if (array_key_exists('send', $result[$formName])) {
                $formParameter['send'][key($result[$formName]['send'])] = $value;
            }
        }

        unset($formParameter['_jsonSerialized']);

        $request->request->set($formName, $formParameter);
    }
}
