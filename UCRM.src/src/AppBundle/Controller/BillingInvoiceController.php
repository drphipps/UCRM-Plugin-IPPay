<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\DataProvider\InvoiceDataProvider;
use AppBundle\Entity\Client;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\InvoiceItem;
use AppBundle\Entity\Financial\InvoiceItemFee;
use AppBundle\Exception\TemplateRenderException;
use AppBundle\Form\InvoiceCommentType;
use AppBundle\Grid\EmailLog\EmailLogGridFactory;
use AppBundle\Handler\Invoice\PdfHandler;
use AppBundle\Interfaces\InvoiceActionsInterface;
use AppBundle\RoutesMap\InvoiceRoutesMap;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Security\SpecialPermission;
use AppBundle\Service\Financial\FinancialTemplateParametersProvider;
use AppBundle\Service\Financial\FinancialTemplateRenderer;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * @Route("/billing/invoice")
 * @PermissionControllerName(InvoiceController::class)
 */
class BillingInvoiceController extends BaseController implements InvoiceActionsInterface
{
    use InvoiceActionsTrait;
    use InvoiceCommonTrait;

    /**
     * @Route(
     *     "/{id}/billing/payments/new/{invoice}",
     *     name="billing_payment_new",
     *     requirements={
     *         "id": "\d+",
     *         "invoice": "\d+"
     *     }
     * )
     * @ParamConverter("invoice", options={"id" = "invoice"})
     * @Method({"GET", "POST"})
     * @Permission("view")
     */
    public function addPaymentAction(Request $request, Client $client, Invoice $invoice): Response
    {
        if (! $this->isPermissionGranted(Permission::EDIT, self::class)) {
            $this->denyAccessUnlessPermissionGranted(SpecialPermission::ALLOWED, SpecialPermission::PAYMENT_CREATE);
        }

        return $this->handleAddPaymentAction($request, $client, $invoice);
    }

    /**
     * @Route("/{id}/approve", name="billing_invoice_approve", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function approveDraftAction(Invoice $invoice): RedirectResponse
    {
        return $this->handleApproveDraft($invoice);
    }

    /**
     * @Route("/{id}/delete", name="billing_invoice_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(Invoice $invoice): RedirectResponse
    {
        return $this->handleDelete($invoice);
    }

    /**
     * @Route("/{id}/download-pdf", name="billing_invoice_download_pdf", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function downloadPdfAction(Invoice $invoice): Response
    {
        return $this->handleDownloadPdf($invoice);
    }

    /**
     * @Route("/{id}/send-email", name="billing_invoice_send", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("view")
     */
    public function sendInvoiceEmailAction(Invoice $invoice): Response
    {
        return $this->handleSendInvoiceEmail($invoice);
    }

    /**
     * @Route("/{id}", name="billing_invoice_show", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("view")
     */
    public function showInvoiceAction(Request $request, Invoice $invoice): Response
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
            'billing/invoice/show.html.twig',
            [
                'client' => $client,
                'invoice' => $invoice,
                'hasFees' => $hasFees,
                'emailLogGrid' => $emailLogGrid,
                'noteForm' => $noteForm->createView(),
                'data' => $this->get(FinancialTemplateParametersProvider::class)
                    ->getInvoiceParameters($invoice, $invoice->getInvoiceStatus() === Invoice::DRAFT),
                'invoiceRoutesMap' => $this->getInvoiceRoutesMap(),
                'hasAttributes' => $this->get(InvoiceDataProvider::class)->hasInvoiceAttributes($invoice),
            ]
        );
    }

    /**
     * @Route("/{id}/pdf", name="billing_invoice_show_pdf", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showInvoicePdfAction(Invoice $invoice): Response
    {
        try {
            $path = $this->get(PdfHandler::class)->getFullInvoicePdfPath($invoice);

            if ($path) {
                $response = new BinaryFileResponse($path);
            } else {
                $response = new Response($this->get(FinancialTemplateRenderer::class)->renderInvoicePdf($invoice));
            }
        } catch (TemplateRenderException | \Dompdf\Exception $exception) {
            return $this->render(
                'invoice_template/render_errors.html.twig',
                [
                    'exception' => $exception,
                ]
            );
        }

        $response->headers->set('Content-Type', 'application/pdf');

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            'invoice.pdf'
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * @Route("/{id}/pdf-regenerate", name="billing_invoice_regenerate_pdf", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("edit")
     * @CsrfToken()
     */
    public function regenerateInvoicePdfAction(Invoice $invoice): Response
    {
        return $this->handleRegeneratePdf($invoice);
    }

    /**
     * @Route("/{id}/use-credit", name="billing_use_credit", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function useCreditAction(Invoice $invoice): RedirectResponse
    {
        return $this->handleUseCredit($invoice);
    }

    /**
     * @Route("/{id}/void", name="billing_invoice_void", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function voidInvoiceAction(Invoice $invoice): RedirectResponse
    {
        return $this->handleVoid($invoice);
    }

    /**
     * @Route("/{id}/uncollectible", name="billing_invoice_uncollectible", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("edit")
     * @CsrfToken()
     */
    public function uncollectibleAction(Invoice $invoice): Response
    {
        return $this->handleUncollectible($invoice);
    }

    /**
     * @Route("/{id}/collectible", name="billing_invoice_collectible", requirements={"id": "\d+"})
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
            $map->show = 'billing_invoice_show';
            $map->paymentNew = 'billing_payment_new';
            $map->invoiceGrid = 'billing_index';
            $map->proformaGrid = 'billing_proforma_index';

            $this->invoiceRoutesMap = $map;
        }

        return $this->invoiceRoutesMap;
    }
}
