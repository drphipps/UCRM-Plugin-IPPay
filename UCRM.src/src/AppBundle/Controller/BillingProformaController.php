<?php
/*
 * @copyright Copyright (c) 2019 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\DataProvider\InvoiceDataProvider;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Option;
use AppBundle\Grid\Invoice\InvoiceGridFactory;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/billing/invoice")
 * @PermissionControllerName(InvoiceController::class)
 */
class BillingProformaController extends BaseController
{
    /**
     * @Route("/proforma-invoices", name="billing_proforma_index")
     * @Method({"GET", "POST"})
     * @Permission("view")
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(InvoiceGridFactory::class)->createProformaGrid();
        if ($response = $grid->processMultiAction()) {
            return $response;
        }
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        $invoicesToSend = $this->em->getRepository(Invoice::class)
            ->existInvoicesToSend($this->getOption(Option::SEND_INVOICE_WITH_ZERO_BALANCE));

        return $this->render(
            'billing/index.html.twig',
            [
                'grid' => $grid,
                'invoicesToSend' => $invoicesToSend,
                'showProformaInvoices' => $this->get(InvoiceDataProvider::class)->showProformaInvoices(),
            ]
        );
    }
}
