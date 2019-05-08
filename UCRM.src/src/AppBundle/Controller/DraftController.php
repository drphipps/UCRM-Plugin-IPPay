<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\DataProvider\InvoiceDataProvider;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Exception\TemplateRenderException;
use AppBundle\Facade\InvoiceFacade;
use AppBundle\Grid\Invoice\DraftGridFactory;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/billing/drafts")
 * @PermissionControllerName(InvoiceController::class)
 */
class DraftController extends BaseController
{
    /**
     * @Route("", name="draft_index")
     * @Method({"GET", "POST"})
     * @Permission("view")
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(DraftGridFactory::class)->create();
        if ($response = $grid->processMultiAction()) {
            return $response;
        }
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'billing/drafts.html.twig',
            [
                'grid' => $grid,
                'showProformaInvoices' => $this->get(InvoiceDataProvider::class)->showProformaInvoices(),
            ]
        );
    }

    /**
     * @Route("/{id}/approve", name="draft_approve", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function approveAction(Invoice $invoice): Response
    {
        try {
            if ($this->get(InvoiceFacade::class)->handleApprove($invoice)) {
                $this->addTranslatedFlash('success', 'Draft has been approved.');
            } else {
                $this->addTranslatedFlash('warning', 'Invoice was already approved.');
            }
        } catch (TemplateRenderException | \Dompdf\Exception $exception) {
            $this->addTranslatedFlash('error', 'Draft could not be approved because of an error in invoice template.');
        }

        return $this->redirectToRoute('draft_index');
    }

    /**
     * @Route("/{id}/delete", name="draft_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(Invoice $invoice): Response
    {
        if ($this->get(InvoiceFacade::class)->handleDelete($invoice)) {
            $this->addTranslatedFlash('success', 'Invoice has been deleted.');
        } else {
            $this->addTranslatedFlash('error', 'Invoice cannot be deleted. You can only delete last invoice with service.');
        }

        return $this->redirectToRoute('draft_index');
    }
}
