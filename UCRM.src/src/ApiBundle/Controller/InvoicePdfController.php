<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\InvoiceController as AppInvoiceController;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Handler\Invoice\PdfHandler;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Service\DownloadResponseFactory;
use AppBundle\Service\Financial\FinancialTemplateRenderer;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(AppInvoiceController::class)
 */
class InvoicePdfController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var DownloadResponseFactory
     */
    private $downloadResponseFactory;

    /**
     * @var PdfHandler
     */
    private $pdfHandler;

    /**
     * @var FinancialTemplateRenderer
     */
    private $financialTemplateRenderer;

    public function __construct(
        DownloadResponseFactory $downloadResponseFactory,
        PdfHandler $pdfHandler,
        FinancialTemplateRenderer $financialTemplateRenderer
    ) {
        $this->downloadResponseFactory = $downloadResponseFactory;
        $this->pdfHandler = $pdfHandler;
        $this->financialTemplateRenderer = $financialTemplateRenderer;
    }

    /**
     * @Get(
     *     "/invoices/{id}/pdf",
     *     name="invoice_get_pdf",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @Permission("view")
     *
     * @throws NotFoundHttpException
     */
    public function getPdfAction(Invoice $invoice)
    {
        if ($path = $this->pdfHandler->getFullInvoicePdfPath($invoice)) {
            return $this->downloadResponseFactory->createFromFile($path);
        }

        $pdf = $this->financialTemplateRenderer->renderInvoicePdf($invoice);

        return $this->downloadResponseFactory->createFromContent(
            $pdf,
            '',
            'pdf',
            'application/pdf',
            strlen($pdf)
        );
    }
}
