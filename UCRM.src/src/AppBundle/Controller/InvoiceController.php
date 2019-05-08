<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\DataProvider\InvoiceDataProvider;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\InvoiceItem;
use AppBundle\Entity\Financial\InvoiceItemFee;
use AppBundle\Form\InvoiceCommentType;
use AppBundle\Grid\EmailLog\EmailLogGridFactory;
use AppBundle\Interfaces\InvoiceActionsInterface;
use AppBundle\RoutesMap\InvoiceRoutesMap;
use AppBundle\Security\Permission;
use AppBundle\Service\Financial\FinancialTemplateParametersProvider;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/client/invoice")
 */
class InvoiceController extends BaseController implements InvoiceActionsInterface
{
    use InvoiceActionsTrait;
    use InvoiceCommonTrait;

    /**
     * @Route("/{id}", name="client_invoice_show", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("view")
     */
    public function showAction(Request $request, Invoice $invoice): Response
    {
        $client = $invoice->getClient();
        $noteForm = $this->createForm(InvoiceCommentType::class, $invoice);
        if ($response = $this->handleNoteAdd($request, $invoice, $noteForm)) {
            return $response;
        }

        $emailLogGrid = $this->get(EmailLogGridFactory::class)->create($invoice);
        if ($parameters = $emailLogGrid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        $hasFees = ! $invoice->getInvoiceItems()
            ->filter(
                function (InvoiceItem $invoiceItem) {
                    return $invoiceItem instanceof InvoiceItemFee;
                }
            )
            ->isEmpty();

        return $this->render(
            'client/invoice/show.html.twig',
            [
                'client' => $client,
                'invoice' => $invoice,
                'hasFees' => $hasFees,
                'emailLogGrid' => $emailLogGrid,
                'noteForm' => $noteForm->createView(),
                'data' => $this->get(FinancialTemplateParametersProvider::class)
                    ->getInvoiceParameters($invoice, $invoice->getInvoiceStatus() === Invoice::DRAFT),
                'invoiceRoutesMap' => $this->getInvoiceRoutesMap(),
                'showProformaInvoices' => $this->get(InvoiceDataProvider::class)->showProformaInvoices(),
                'hasAttributes' => $this->get(InvoiceDataProvider::class)->hasInvoiceAttributes($invoice),
            ]
        );
    }

    /**
     * @Route("/{id}/download-pdf", name="client_invoice_download_pdf", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function downloadPdfAction(Invoice $invoice): Response
    {
        return $this->handleDownloadPdf($invoice);
    }

    /**
     * @Route("/{id}/approve", name="client_invoice_approve", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function approveDraftAction(Invoice $invoice): RedirectResponse
    {
        return $this->handleApproveDraft($invoice);
    }

    /**
     * @Route("/{id}/void", name="client_invoice_void", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function voidInvoiceAction(Invoice $invoice): RedirectResponse
    {
        return $this->handleVoid($invoice, ['id' => $invoice->getClient()->getId()]);
    }

    /**
     * @Route("/{id}/delete", name="client_invoice_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(Invoice $invoice): Response
    {
        return $this->handleDelete($invoice, ['id' => $invoice->getClient()->getId()]);
    }

    /**
     * @Route("/{id}/send-email", name="client_invoice_send", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("view")
     */
    public function sendInvoiceEmailAction(Invoice $invoice): RedirectResponse
    {
        return $this->handleSendInvoiceEmail($invoice);
    }

    /**
     * @Route("/{id}/pdf-regenerate", name="client_invoice_regenerate_pdf", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("edit")
     * @CsrfToken()
     */
    public function regenerateInvoicePdfAction(Invoice $invoice): Response
    {
        return $this->handleRegeneratePdf($invoice);
    }

    /**
     * @Route("/{id}/use-credit", name="client_use_credit", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function useCreditAction(Invoice $invoice): RedirectResponse
    {
        return $this->handleUseCredit($invoice);
    }

    /**
     * @Route("/{id}/uncollectible", name="client_invoice_uncollectible", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("edit")
     * @CsrfToken()
     */
    public function uncollectibleAction(Invoice $invoice): Response
    {
        return $this->handleUncollectible($invoice);
    }

    /**
     * @Route("/{id}/collectible", name="client_invoice_collectible", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("edit")
     * @CsrfToken()
     */
    public function collectibleAction(Invoice $invoice): Response
    {
        return $this->handleCollectible($invoice);
    }

    public function getInvoiceRoutesMap(): InvoiceRoutesMap
    {
        if (! $this->invoiceRoutesMap) {
            $map = new InvoiceRoutesMap();
            $map->show = 'client_invoice_show';
            $map->paymentNew = 'client_payment_new';
            $map->invoiceGrid = 'client_show_invoices';
            $map->proformaGrid = 'client_show_proformas';

            $this->invoiceRoutesMap = $map;
        }

        return $this->invoiceRoutesMap;
    }
}
