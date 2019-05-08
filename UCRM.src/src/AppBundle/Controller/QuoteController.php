<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\DataProvider\InvoiceDataProvider;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\Financial\QuoteItem;
use AppBundle\Entity\Financial\QuoteItemService;
use AppBundle\Form\QuoteCommentType;
use AppBundle\Grid\EmailLog\EmailLogGridFactory;
use AppBundle\Interfaces\QuoteActionsInterface;
use AppBundle\RoutesMap\QuoteRoutesMap;
use AppBundle\Security\Permission;
use AppBundle\Service\Financial\FinancialTemplateParametersProvider;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/client/quote")
 */
class QuoteController extends BaseController implements QuoteActionsInterface
{
    use QuoteActionsTrait;
    use QuoteCommonTrait;

    /**
     * @Route("/{id}", name="client_quote_show", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("view")
     */
    public function showAction(Request $request, Quote $quote): Response
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
            'client/quote/show.html.twig',
            [
                'client' => $client,
                'quote' => $quote,
                'emailLogGrid' => $emailLogGrid,
                'data' => $this->get(FinancialTemplateParametersProvider::class)->getQuoteParameters($quote),
                'noteForm' => $noteForm->createView(),
                'hasServices' => $hasServices,
                'showProformaInvoices' => $this->get(InvoiceDataProvider::class)->showProformaInvoices(),
            ]
        );
    }

    /**
     * @Route("/{id}/delete", name="client_quote_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(Quote $quote): Response
    {
        return $this->handleDelete($quote, ['id' => $quote->getClient()->getId()]);
    }

    /**
     * @Route("/{id}/download-pdf", name="client_quote_download_pdf", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function downloadPdfAction(Quote $quote): Response
    {
        return $this->handleDownloadPdf($quote);
    }

    /**
     * @Route("/{id}/send-email", name="client_quote_send", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("view")
     */
    public function sendQuoteEmailAction(Quote $quote): RedirectResponse
    {
        return $this->handleSendQuoteEmail($quote);
    }

    /**
     * @Route("/{id}/pdf-regenerate", name="client_quote_regenerate_pdf", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("edit")
     * @CsrfToken()
     */
    public function regenerateQuotePdfAction(Quote $invoice): Response
    {
        return $this->handleRegeneratePdf($invoice);
    }

    /**
     * @Route("/{id}/accept", name="client_quote_accept", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function acceptAction(Quote $quote): RedirectResponse
    {
        return $this->handleAcceptQuote($quote);
    }

    /**
     * @Route("/{id}/reject", name="client_quote_reject", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function rejectAction(Quote $quote): RedirectResponse
    {
        return $this->handleRejectQuote($quote);
    }

    /**
     * @Route("/{id}/reopen", name="client_quote_reopen", requirements={"id": "\d+"})
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
            $map->show = 'client_quote_show';
            $map->quoteGrid = 'client_show';

            $this->quoteRoutesMap = $map;
        }

        return $this->quoteRoutesMap;
    }
}
