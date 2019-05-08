<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Financial\FinancialEmailSender;
use AppBundle\Entity\Client;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\NotificationTemplate;
use AppBundle\Entity\Option;
use AppBundle\Entity\Payment;
use AppBundle\Exception\TemplateRenderException;
use AppBundle\Facade\Exception\CannotDeleteProcessedProformaException;
use AppBundle\Facade\Exception\CannotVoidProcessedProformaException;
use AppBundle\Facade\InvoiceFacade;
use AppBundle\Facade\PaymentFacade;
use AppBundle\Form\InvoiceCommentType;
use AppBundle\Form\PaymentType;
use AppBundle\Handler\Invoice\PdfHandler;
use AppBundle\RoutesMap\InvoiceRoutesMap;
use AppBundle\Security\Permission;
use AppBundle\Security\SpecialPermission;
use AppBundle\Util\Formatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @property Container              $container
 * @property EntityManagerInterface $em
 */
trait InvoiceActionsTrait
{
    /**
     * @var InvoiceRoutesMap|null
     */
    private $invoiceRoutesMap;

    private function handleAddPaymentAction(Request $request, Client $client, ?Invoice $invoice = null): Response
    {
        if (! $this->isPermissionGranted(Permission::EDIT, self::class)) {
            $this->denyAccessUnlessPermissionGranted(SpecialPermission::ALLOWED, SpecialPermission::PAYMENT_CREATE);
        }

        $payment = new Payment();
        $payment->setClient($client);
        $payment->setCurrency($client->getOrganization()->getCurrency());
        $payment->setCreatedDate(new \DateTime());
        $payment->setUser($this->getUser());
        $payment->setSendReceipt($this->getOption(Option::SEND_PAYMENT_RECEIPTS));

        $options = [
            'action' => $this->generateUrl(
                $this->getInvoiceRoutesMap()->paymentNew,
                [
                    'id' => $client->getId(),
                    'invoice' => $invoice ? $invoice->getId() : null,
                ]
            ),
            'attr' => [
                'autocomplete' => 'off',
            ],
            'organization' => $client->getOrganization(),
            'client' => $client,
        ];

        /** @var FormInterface $form */
        $form = $this->createForm(PaymentType::class, $payment, $options);
        if ($invoice) {
            $form->get('invoices')->setData([$invoice]);
            $form->get('amount')->setData($invoice->getAmountToPay());
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->container->get(PaymentFacade::class)->handleCreate(
                $payment,
                $form->get('invoices')->getData()->toArray(),
                null,
                $payment->isSendReceipt()
            );
            $this->addTranslatedFlash('success', 'Payment has been created.');

            if (! $client->getBillingEmails() && $payment->isSendReceipt()) {
                $this->addTranslatedFlash('warning', 'Client does not have Billing email.');
            }

            if ($payment->getCredit()) {
                $this->addTranslatedFlash(
                    'info',
                    '%amount% has been added to credit.',
                    null,
                    [
                        '%amount%' => $this->container->get(Formatter::class)->formatCurrency(
                            $payment->getCredit()->getAmount(),
                            $payment->getCurrency()->getCode()
                        ),
                    ]
                );
            }

            if ($invoice) {
                return $this->createAjaxRedirectResponse(
                    $this->getInvoiceRoutesMap()->show,
                    [
                        'id' => $invoice->getId(),
                    ]
                );
            }

            return $this->createAjaxRedirectResponse(
                'client_show_payments',
                [
                    'id' => $client->getId(),
                ]
            );
        }

        return $this->render(
            'payments/components/add_form.html.twig',
            [
                'form' => $form->createView(),
                'client' => $client,
            ]
        );
    }

    private function handleApproveDraft(Invoice $invoice): RedirectResponse
    {
        try {
            if ($this->container->get(InvoiceFacade::class)->handleApprove($invoice)) {
                $this->addTranslatedFlash('success', 'Draft has been approved.');
            } else {
                $this->addTranslatedFlash('warning', 'Invoice was already approved.');
            }
        } catch (TemplateRenderException | \Dompdf\Exception $exception) {
            $this->addTranslatedFlash('error', 'Draft could not be approved because of an error in invoice template.');
        }

        return $this->redirectToRoute($this->getInvoiceRoutesMap()->show, ['id' => $invoice->getId()]);
    }

    private function handleDelete(Invoice $invoice, ?array $parameters = []): RedirectResponse
    {
        try {
            $this->container->get(InvoiceFacade::class)->handleDelete($invoice);
            $this->addTranslatedFlash('success', 'Invoice has been deleted.');
        } catch (CannotDeleteProcessedProformaException $exception) {
            $this->addTranslatedFlash(
                'error',
                'Processed proforma invoice cannot be deleted.'
            );
        }

        return $this->redirectToRoute(
            $invoice->isProforma()
                ? $this->getInvoiceRoutesMap()->proformaGrid
                : $this->getInvoiceRoutesMap()->invoiceGrid,
            $parameters
        );
    }

    private function handleNoteAdd(Request $request, Invoice $invoice, FormInterface $noteForm): ?Response
    {
        $noteForm->handleRequest($request);

        if ($noteForm->isSubmitted() && $noteForm->isValid()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);

            $this->em->flush();
            $this->addTranslatedFlash('success', 'Note has been saved.');

            if ($request->isXmlHttpRequest()) {
                $noteForm = $this->createForm(InvoiceCommentType::class, $invoice);
                assert($noteForm instanceof FormInterface);

                $this->invalidateTemplate(
                    'invoice__note',
                    'client/invoice/components/show/notes.html.twig',
                    [
                        'invoice' => $invoice,
                        'client' => $invoice->getClient(),
                        'noteForm' => $noteForm->createView(),
                    ]
                );

                return $this->createAjaxResponse();
            }

            return $this->redirectToRoute($this->getInvoiceRoutesMap()->show, ['id' => $invoice->getId()]);
        }

        return null;
    }

    private function handleSendInvoiceEmail(Invoice $invoice): RedirectResponse
    {
        if ($invoice->getInvoiceStatus() === Invoice::DRAFT) {
            $this->addTranslatedFlash('error', 'Invoice drafts cannot be sent to client, they must be approved first.');
        } else {
            $this->container->get(FinancialEmailSender::class)->send(
                $invoice,
                $invoice->isProforma()
                    ? NotificationTemplate::CLIENT_NEW_PROFORMA_INVOICE
                    : NotificationTemplate::CLIENT_NEW_INVOICE
            );
            $this->addTranslatedFlash('success', 'Invoice has been queued for sending.');
        }

        return $this->redirectToRoute($this->getInvoiceRoutesMap()->show, ['id' => $invoice->getId()]);
    }

    private function handleUseCredit(Invoice $invoice): RedirectResponse
    {
        $this->container->get(InvoiceFacade::class)->handleUseCredit($invoice);

        $this->addTranslatedFlash('success', 'Available credit used to cover invoice amount.');

        return $this->redirectToRoute($this->getInvoiceRoutesMap()->show, ['id' => $invoice->getId()]);
    }

    private function handleVoid(Invoice $invoice, ?array $parameters = []): RedirectResponse
    {
        try {
            if ($this->container->get(InvoiceFacade::class)->handleVoid($invoice)) {
                $this->addTranslatedFlash('success', 'Invoice has been voided.');
            } else {
                $this->addTranslatedFlash('warning', 'Invoice is already void.');
            }
        } catch (CannotVoidProcessedProformaException $exception) {
            $this->addTranslatedFlash(
                'error',
                'Processed proforma invoice cannot be voided.'
            );
        }

        return $this->redirectToRoute(
            $invoice->isProforma()
                ? $this->getInvoiceRoutesMap()->proformaGrid
                : $this->getInvoiceRoutesMap()->invoiceGrid,
            $parameters
        );
    }

    private function handleRegeneratePdf(Invoice $invoice): RedirectResponse
    {
        try {
            $this->container->get(PdfHandler::class)->getFullInvoicePdfPath($invoice, true);
            $this->addTranslatedFlash('success', 'Invoice PDF has been regenerated.');
        } catch (TemplateRenderException $exception) {
            $this->addTranslatedFlash(
                'error',
                'Invoice PDF could not be regenerated because of an error in invoice template.'
            );
        } catch (IOException | \InvalidArgumentException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute($this->getInvoiceRoutesMap()->show, ['id' => $invoice->getId()]);
    }

    private function handleUncollectible(Invoice $invoice): RedirectResponse
    {
        $this->container->get(InvoiceFacade::class)->handleUncollectible($invoice);

        $this->addTranslatedFlash('success', 'Invoice has been marked as uncollectible.');

        return $this->redirectToRoute($this->getInvoiceRoutesMap()->show, ['id' => $invoice->getId()]);
    }

    private function handleCollectible(Invoice $invoice): RedirectResponse
    {
        $this->container->get(InvoiceFacade::class)->handleCollectible($invoice);

        $this->addTranslatedFlash('success', 'Invoice has been marked as collectible.');

        return $this->redirectToRoute($this->getInvoiceRoutesMap()->show, ['id' => $invoice->getId()]);
    }
}
