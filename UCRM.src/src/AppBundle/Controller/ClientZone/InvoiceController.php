<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller\ClientZone;

use AppBundle\Controller\InvoiceCommonTrait;
use AppBundle\DataProvider\PaymentTokenDataProvider;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Facade\OnlinePaymentFacade;
use AppBundle\Factory\Financial\PaymentTokenFactory;
use AppBundle\Grid\ClientZone\InvoiceGridFactory;
use AppBundle\Security\Permission;
use AppBundle\Service\Financial\FinancialTemplateParametersProvider;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/client-zone/invoices")
 */
class InvoiceController extends BaseController
{
    use InvoiceCommonTrait;

    /**
     * @Route("", name="client_zone_invoice_index")
     * @Method("GET")
     * @Permission("guest")
     */
    public function indexAction(Request $request): Response
    {
        $client = $this->getClient();
        $grid = $this->get(InvoiceGridFactory::class)->create($client);
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'client_zone/invoice/index.html.twig',
            [
                'client' => $client,
                'invoices' => $grid,
            ]
        );
    }

    /**
     * @Route("/{id}", name="client_zone_invoice_show", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("guest")
     */
    public function showAction(Invoice $invoice): Response
    {
        $this->verifyOwnership($invoice);

        return $this->render(
            'client_zone/invoice/show.html.twig',
            [
                'invoice' => $invoice,
                'paymentGatewayAvailable' => $invoice->getOrganization()->hasPaymentGateway($this->isSandbox()),
                'data' => $this->get(FinancialTemplateParametersProvider::class)->getInvoiceParameters($invoice),
                'paymentToken' => $invoice->getPaymentToken(),
                'invoiceIdsWithPendingPayments' => $this->get(PaymentTokenDataProvider::class)->getInvoiceIdsWithPendingPayments($invoice->getClient()),
            ]
        );
    }

    /**
     * @Route("/{id}/download-pdf", name="client_zone_invoice_download_pdf", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("guest")
     */
    public function downloadPdfAction(Invoice $invoice): Response
    {
        $this->verifyOwnership($invoice);

        return $this->handleDownloadPdf($invoice);
    }

    /**
     * @Route("/{id}/pay", name="client_zone_invoice_pay", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("guest")
     */
    public function payAction(Invoice $invoice): Response
    {
        $this->verifyOwnership($invoice);

        $token = $invoice->getPaymentToken();
        if (! $token) {
            $token = $this->get(PaymentTokenFactory::class)->create($invoice);
            $this->get(OnlinePaymentFacade::class)->handleCreatePaymentToken($token);
        }

        return $this->redirectToRoute(
            'online_payment_pay',
            [
                'token' => $token->getToken(),
            ]
        );
    }
}
