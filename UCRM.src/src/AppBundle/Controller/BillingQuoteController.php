<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\DataProvider\InvoiceDataProvider;
use AppBundle\Entity\Client;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\Financial\QuoteItem;
use AppBundle\Entity\Financial\QuoteItemService;
use AppBundle\Exception\TemplateRenderException;
use AppBundle\Form\ChooseClientType;
use AppBundle\Form\QuoteCommentType;
use AppBundle\Grid\EmailLog\EmailLogGridFactory;
use AppBundle\Grid\Quote\QuoteGridFactory;
use AppBundle\Handler\Quote\PdfHandler;
use AppBundle\Interfaces\QuoteActionsInterface;
use AppBundle\RoutesMap\QuoteRoutesMap;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Service\Financial\FinancialTemplateParametersProvider;
use AppBundle\Service\Financial\FinancialTemplateRenderer;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * @Route("/billing")
 * @PermissionControllerName(QuoteController::class)
 */
class BillingQuoteController extends BaseController implements QuoteActionsInterface
{
    use QuoteActionsTrait;
    use QuoteCommonTrait;

    /**
     * @Route("/quotes", name="quote_index")
     * @Method({"GET", "POST"})
     * @Permission("view")
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(QuoteGridFactory::class)->create();
        if ($response = $grid->processMultiAction()) {
            return $response;
        }
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'billing/quote/index.html.twig',
            [
                'grid' => $grid,
                'showProformaInvoices' => $this->get(InvoiceDataProvider::class)->showProformaInvoices(),
            ]
        );
    }

    /**
     * @Route("/quotes/new", name="quote_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newQuoteAction(Request $request): Response
    {
        $form = $this->createForm(
            ChooseClientType::class,
            null,
            [
                'include_leads' => true,
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Client $client */
            $client = $form->get('client')->getData();

            return $this->createAjaxRedirectResponse(
                'client_quote_new',
                [
                    'id' => $client->getId(),
                ]
            );
        }

        return $this->render(
            'billing/add_quote.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/quote/{id}/delete", name="quote_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(Quote $quote): RedirectResponse
    {
        return $this->handleDelete($quote);
    }

    /**
     * @Route("/quote/{id}/download-pdf", name="quote_download_pdf", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function downloadPdfAction(Quote $quote): Response
    {
        return $this->handleDownloadPdf($quote);
    }

    /**
     * @Route("/quote/{id}", name="quote_show", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("view")
     */
    public function showQuoteAction(Request $request, Quote $quote): Response
    {
        $client = $quote->getClient();

        $noteForm = $this->createForm(QuoteCommentType::class, $quote);
        if ($response = $this->handleNoteAdd($request, $quote, $noteForm)) {
            return $response;
        }

        $emailLogGrid = $this->get(EmailLogGridFactory::class)->create(null, null, null, $quote);
        if ($parameters = $emailLogGrid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        $hasServices = ! $quote->getQuoteItems()
            ->filter(
                function (QuoteItem $item) {
                    return $item instanceof QuoteItemService;
                }
            )
            ->isEmpty();

        return $this->render(
            'billing/quote/show.html.twig',
            [
                'client' => $client,
                'quote' => $quote,
                'emailLogGrid' => $emailLogGrid,
                'noteForm' => $noteForm->createView(),
                'data' => $this->get(FinancialTemplateParametersProvider::class)->getQuoteParameters($quote),
                'hasServices' => $hasServices,
            ]
        );
    }

    /**
     * @Route("/{id}/pdf", name="billing_quote_show_pdf", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showQuotePdfAction(Quote $quote): Response
    {
        try {
            $path = $this->get(PdfHandler::class)->getFullQuotePdfPath($quote);
            if ($path) {
                $response = new BinaryFileResponse($path);
            } else {
                $response = new Response($this->get(FinancialTemplateRenderer::class)->renderQuotePdf($quote));
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
     * @Route("/{id}/pdf-regenerate", name="billing_quote_regenerate_pdf", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("edit")
     * @CsrfToken()
     */
    public function regenerateQuotePdfAction(Quote $invoice): Response
    {
        return $this->handleRegeneratePdf($invoice);
    }

    /**
     * @Route("/{id}/send-email", name="quote_send", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("view")
     */
    public function sendInvoiceEmailAction(Quote $quote): RedirectResponse
    {
        return $this->handleSendQuoteEmail($quote);
    }

    /**
     * @Route("/{id}/accept", name="quote_accept", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function acceptAction(Quote $quote): RedirectResponse
    {
        return $this->handleAcceptQuote($quote);
    }

    /**
     * @Route("/{id}/reject", name="quote_reject", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function rejectAction(Quote $quote): RedirectResponse
    {
        return $this->handleRejectQuote($quote);
    }

    /**
     * @Route("/{id}/reopen", name="quote_reopen", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function reopenAction(Quote $quote): RedirectResponse
    {
        return $this->handleReopenQuote($quote);
    }

    public function getQuoteRoutesMap(): QuoteRoutesMap
    {
        if (! $this->quoteRoutesMap) {
            $map = new QuoteRoutesMap();
            $map->show = 'quote_show';
            $map->quoteGrid = 'quote_index';

            $this->quoteRoutesMap = $map;
        }

        return $this->quoteRoutesMap;
    }
}
