<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Component\Validator\ValidationHttpException;
use ApiBundle\Component\Validator\Validator;
use ApiBundle\Map\PaymentMap;
use ApiBundle\Mapper\PaymentMapper;
use ApiBundle\Request\PaymentCollectionRequest;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Component\Payment\ReceiptSender;
use AppBundle\Controller\PaymentController as AppPaymentController;
use AppBundle\DataProvider\PaymentDataProvider;
use AppBundle\Entity\Payment;
use AppBundle\Entity\PaymentCustom;
use AppBundle\Exception\TemplateRenderException;
use AppBundle\Facade\Exception\InvalidPaymentCurrencyException;
use AppBundle\Facade\PaymentFacade;
use AppBundle\Handler\Payment\PdfHandler;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Security\SpecialPermission;
use AppBundle\Service\DownloadResponseFactory;
use AppBundle\Util\DateTimeFactory;
use AppBundle\Util\Helpers;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(AppPaymentController::class)
 */
class PaymentController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var PaymentFacade
     */
    private $facade;

    /**
     * @var PaymentDataProvider
     */
    private $dataProvider;

    /**
     * @var PaymentMapper
     */
    private $mapper;

    /**
     * @var ReceiptSender
     */
    private $receiptSender;

    /**
     * @var DownloadResponseFactory
     */
    private $downloadResponseFactory;

    /**
     * @var PdfHandler
     */
    private $pdfHandler;

    public function __construct(
        Validator $validator,
        PaymentFacade $facade,
        PaymentDataProvider $dataProvider,
        PaymentMapper $mapper,
        ReceiptSender $receiptSender,
        DownloadResponseFactory $downloadResponseFactory,
        PdfHandler $pdfHandler
    ) {
        $this->validator = $validator;
        $this->facade = $facade;
        $this->dataProvider = $dataProvider;
        $this->mapper = $mapper;
        $this->receiptSender = $receiptSender;
        $this->downloadResponseFactory = $downloadResponseFactory;
        $this->pdfHandler = $pdfHandler;
    }

    /**
     * @Get("/payments/{id}", name="payment_get", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(Payment $payment): View
    {
        return $this->view(
            $this->mapper->reflect($payment)
        );
    }

    /**
     * @Delete(
     *     "/payments/{id}",
     *     name="payment_delete",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("edit")
     */
    public function deleteAction(Payment $payment): View
    {
        if (! $this->facade->handleDelete($payment)) {
            throw new HttpException(422, 'Payment with a refund cannot be deleted.');
        }

        return $this->view(null, 200);
    }

    /**
     * @Patch("/payments/{id}/send-receipt", name="payment_get_and_send_receipt", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ViewHandler()
     * @Permission("view")
     *
     * @throws ValidationHttpException
     */
    public function getAndSendReceiptAction(Payment $payment): View
    {
        if (! $payment->getClient()) {
            throw new ValidationHttpException(
                [
                    'clientId' => [
                        'Receipts can be sent for matched payments only.',
                    ],
                ]
            );
        }

        if (! $payment->getClient()->hasBillingEmail()) {
            throw new HttpException(422, 'Email could not be sent, because client has no email set.');
        }

        try {
            $this->receiptSender->send($payment);
        } catch (TemplateRenderException $exception) {
            throw new HttpException(422, 'Receipt has not been sent. Receipt template is invalid.');
        }

        return $this->view(
            $this->mapper->reflect($payment)
        );
    }

    /**
     * @Get(
     *     "/payments",
     *     name="payments_collection_get",
     *     options={"method_prefix"=false},
     * )
     * @ViewHandler()
     * @Permission("view")
     * @QueryParam(
     *     name="createdDateFrom",
     *     requirements="\d{4}-\d{2}-\d{2}",
     *     strict=true,
     *     nullable=true,
     *     description="limit collection starting on date (including)"
     * )
     * @QueryParam(
     *     name="createdDateTo",
     *     requirements="\d{4}-\d{2}-\d{2}",
     *     strict=true,
     *     nullable=true,
     *     description="limit collection ending on date (including)"
     * )
     * @QueryParam(
     *     name="clientId",
     *     requirements="\d+",
     *     strict=true,
     *     nullable=true,
     *     description="client ID"
     * )
     * @QueryParam(
     *     name="order",
     *     requirements="id|createdDate|amount",
     *     strict=true,
     *     nullable=true,
     *     description="order by (id|createdDate|amount)"
     * )
     * @QueryParam(
     *     name="direction",
     *     requirements="ASC|DESC",
     *     strict=true,
     *     nullable=true,
     *     description="direction of sort - ascending (ASC) or descending (DESC)"
     * )
     * @QueryParam(
     *     name="limit",
     *     requirements="\d+",
     *     strict=true,
     *     nullable=true,
     *     description="max results limit"
     * )
     * @QueryParam(
     *     name="offset",
     *     requirements="\d+",
     *     strict=true,
     *     nullable=true,
     *     description="results offset"
     * )
     */
    public function getCollectionAction(ParamFetcherInterface $paramFetcher): View
    {
        $request = new PaymentCollectionRequest();

        if ($startDate = $paramFetcher->get('createdDateFrom')) {
            try {
                $request->startDate = DateTimeFactory::createDate($startDate);
            } catch (\InvalidArgumentException $exception) {
                throw new HttpException(400, $exception->getMessage(), $exception);
            }
        }
        if ($endDate = $paramFetcher->get('createdDateTo')) {
            try {
                $request->endDate = DateTimeFactory::createDate($endDate)->setTime(23, 59, 59);
            } catch (\InvalidArgumentException $exception) {
                throw new HttpException(400, $exception->getMessage(), $exception);
            }
        }

        $request->clientId = Helpers::typeCastNullable('int', $paramFetcher->get('clientId'));
        $request->order = $paramFetcher->get('order');
        $request->direction = $paramFetcher->get('direction');
        $request->limit = Helpers::typeCastNullable('int', $paramFetcher->get('limit'));
        $request->offset = Helpers::typeCastNullable('int', $paramFetcher->get('offset'));

        $payments = $this->dataProvider->getCollection($request);

        return $this->view(
            $this->mapper->reflectCollection($payments)
        );
    }

    /**
     * @Post("/payments", name="payment_add", options={"method_prefix"=false})
     * @ParamConverter("paymentMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("view")
     */
    public function postAction(PaymentMap $paymentMap, string $version): View
    {
        if (! $this->isPermissionGranted(Permission::EDIT, AppPaymentController::class)) {
            $this->denyAccessUnlessPermissionGranted(SpecialPermission::ALLOWED, SpecialPermission::PAYMENT_CREATE);
        }

        $payment = new Payment();
        $paymentCustom = new PaymentCustom();
        $this->facade->setDefaults($payment, $paymentCustom);
        $this->mapper->map($paymentMap, $payment, $paymentCustom);

        $this->validator->validatePostpone($payment, $this->mapper->getFieldsDifference());
        if ($payment->getMethod() === Payment::METHOD_CUSTOM) {
            $this->validator->validatePostpone($paymentCustom, $this->mapper->getFieldsDifference());
        } else {
            $paymentCustom = null;
        }
        $this->validator->throwErrors();

        if ($payment->getClient() && $payment->getClient()->getIsLead()) {
            throw new ValidationHttpException([], 'This action is not possible, while the client is lead.');
        }

        $ids = $paymentMap->invoiceIds ?: [];
        if ($paymentMap->invoiceId) {
            $ids[] = $paymentMap->invoiceId;
        }

        try {
            if ($paymentMap->applyToInvoicesAutomatically) {
                $this->facade->handleCreateWithoutInvoiceIds($payment, $paymentCustom);
            } else {
                $this->facade->handleCreateWithInvoiceIds($payment, $ids, $paymentCustom);
            }
        } catch (InvalidPaymentCurrencyException $e) {
            throw new ValidationHttpException(
                [
                    'invoiceIds' => [
                        sprintf(
                            'Invoice with ID "%d" has currency "%s" which does not match the payment currency "%s".',
                            $e->getInvoice()->getId(),
                            $e->getInvoice()->getCurrency()->getCode(),
                            $e->getPayment()->getCurrency()->getCode()
                        ),
                    ],
                ]
            );
        }

        return $this->routeRedirectViewWithData(
            $this->mapper->reflect($payment),
            'api_payment_get',
            [
                'version' => $version,
                'id' => $payment->getId(),
            ]
        );
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
