<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller\ClientZone;

use AppBundle\Controller\QuoteCommonTrait;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Grid\ClientZone\QuoteGridFactory;
use AppBundle\Security\Permission;
use AppBundle\Service\Financial\FinancialTemplateParametersProvider;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/client-zone/quote")
 */
class QuoteController extends BaseController
{
    use QuoteCommonTrait;

    /**
     * @Route("", name="client_zone_quote_index")
     * @Method("GET")
     * @Permission("guest")
     */
    public function indexAction(Request $request): Response
    {
        $client = $this->getClient();
        $grid = $this->get(QuoteGridFactory::class)->create($client);
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'client_zone/quote/index.html.twig',
            [
                'client' => $client,
                'quotes' => $grid,
            ]
        );
    }

    /**
     * @Route("/{id}", name="client_zone_quote_show", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("guest")
     */
    public function showAction(Quote $quote): Response
    {
        $this->verifyOwnership($quote);

        return $this->render(
            'client_zone/quote/show.html.twig',
            [
                'quote' => $quote,
                'data' => $this->get(FinancialTemplateParametersProvider::class)->getQuoteParameters($quote),
                'client' => $quote->getClient(),
            ]
        );
    }

    /**
     * @Route("/{id}/download-pdf", name="client_zone_quote_download_pdf", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("guest")
     */
    public function downloadPdfAction(Quote $quote): Response
    {
        $this->verifyOwnership($quote);

        return $this->handleDownloadPdf($quote);
    }
}
