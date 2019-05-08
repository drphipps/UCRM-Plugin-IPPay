<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\PaymentController as AppPaymentController;
use AppBundle\Entity\Payment;
use AppBundle\Handler\Payment\PdfHandler;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Service\DownloadResponseFactory;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(AppPaymentController::class)
 */
class PaymentPdfController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var DownloadResponseFactory
     */
    private $downloadResponseFactory;

    /**
     * @var PdfHandler
     */
    private $pdfHandler;

    public function __construct(
        DownloadResponseFactory $downloadResponseFactory,
        PdfHandler $pdfHandler
    ) {
        $this->downloadResponseFactory = $downloadResponseFactory;
        $this->pdfHandler = $pdfHandler;
    }

    /**
     * @Get(
     *     "/payments/{id}/pdf",
     *     name="payment_get_pdf",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @Permission("view")
     *
     * @throws NotFoundHttpException
     */
    public function getPdfAction(Payment $payment)
    {
        if ($pdfPAth = $this->pdfHandler->getFullPaymentReceiptPdfPath($payment)) {
            return $this->downloadResponseFactory->createFromFile($pdfPAth);
        }

        throw new HttpException(422, 'Receipts can be downloaded for matched payments only.');
    }
}
