<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Component\Validator\ValidationHttpException;
use ApiBundle\Component\Validator\Validator;
use ApiBundle\Map\InvoiceMap;
use ApiBundle\Map\InvoiceNewMap;
use ApiBundle\Mapper\InvoiceMapper;
use ApiBundle\Request\InvoiceCollectionRequest;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Component\Financial\FinancialEmailSender;
use AppBundle\Controller\InvoiceController as AppInvoiceController;
use AppBundle\DataProvider\InvoiceDataProvider;
use AppBundle\Entity\Client;
use AppBundle\Entity\Financial\FinancialInterface;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\NotificationTemplate;
use AppBundle\Exception\TemplateRenderException;
use AppBundle\Facade\Exception\CannotDeleteProcessedProformaException;
use AppBundle\Facade\InvoiceFacade;
use AppBundle\Factory\Financial\FinancialFactory;
use AppBundle\Handler\Invoice\PdfHandler;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
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
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(AppInvoiceController::class)
 */
class InvoiceController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var InvoiceFacade
     */
    private $facade;

    /**
     * @var InvoiceMapper
     */
    private $mapper;

    /**
     * @var FinancialFactory
     */
    private $financialFactory;

    /**
     * @var InvoiceDataProvider
     */
    private $dataProvider;

    /**
     * @var FinancialEmailSender
     */
    private $financialEmailSender;

    public function __construct(
        Validator $validator,
        InvoiceFacade $facade,
        InvoiceMapper $mapper,
        FinancialFactory $financialFactory,
        InvoiceDataProvider $dataProvider,
        FinancialEmailSender $financialEmailSender
    ) {
        $this->validator = $validator;
        $this->facade = $facade;
        $this->mapper = $mapper;
        $this->financialFactory = $financialFactory;
        $this->dataProvider = $dataProvider;
        $this->financialEmailSender = $financialEmailSender;
    }

    /**
     * @Get("/invoices/{id}", name="invoice_get", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @Get("/clients/invoices/{id}", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(Invoice $invoice): View
    {
        return $this->view(
            $this->mapper->reflect($invoice)
        );
    }

    /**
     * @deprecated use getCollectionAction with clientId param
     *
     * @Get(
     *     "/clients/{id}/invoices",
     *     name="client_invoice_collection_get",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("view")
     */
    public function getCollectionOldAction(Client $client): View
    {
        if ($client->isDeleted()) {
            throw new NotFoundHttpException('Client is archived. All actions are prohibited. You can only restore the client.');
        }

        $request = new InvoiceCollectionRequest();
        $request->clientId = $client->getId();
        $invoices = $this->dataProvider->getInvoiceCollection($request);

        return $this->view(
            $this->mapper->reflectCollection($invoices)
        );
    }

    /**
     * @Get(
     *     "/invoices",
     *     name="invoice_collection_get",
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
     *     name="organizationId",
     *     requirements="\d+",
     *     strict=true,
     *     nullable=true,
     *     description="organization ID"
     * )
     * @QueryParam(
     *     name="clientId",
     *     requirements="\d+",
     *     strict=true,
     *     nullable=true,
     *     description="client ID"
     * )
     * @QueryParam(
     *     name="number",
     *     nullable=true,
     *     description="search by invoice number"
     * )
     * @QueryParam(
     *     name="statuses",
     *     requirements=@Assert\All(@Assert\Choice(Invoice::STATUSES)),
     *     strict=true,
     *     nullable=true,
     *     description="select only invoices in one of the given statuses"
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
     * @QueryParam(
     *     name="order",
     *     requirements="clientFirstName|clientLastName|createdDate",
     *     strict=true,
     *     nullable=true,
     *     description="order by (clientFirstName|clientLastName|createdDate)"
     * )
     * @QueryParam(
     *     name="direction",
     *     requirements="ASC|DESC",
     *     strict=true,
     *     nullable=true,
     *     description="direction of sort - ascending (ASC) or descending (DESC)"
     * )
     * @QueryParam(
     *     name="overdue",
     *     requirements="[01]",
     *     strict=true,
     *     nullable=true,
     *     description="filter overdue invoices"
     * )
     * @QueryParam(
     *     name="customAttributeKey",
     *     nullable=true,
     *     description="search by custom attribute, you have to specify customAttributeValue as well"
     * )
     * @QueryParam(
     *     name="customAttributeValue",
     *     nullable=true
     * )
     * @QueryParam(
     *     name="proforma",
     *     requirements="[01]",
     *     strict=true,
     *     nullable=true,
     *     description="filter proforma invoices"
     * )
     */
    public function getCollectionAction(ParamFetcherInterface $paramFetcher): View
    {
        $request = new InvoiceCollectionRequest();

        if ($startDate = $paramFetcher->get('createdDateFrom')) {
            try {
                $request->startDate = DateTimeFactory::createDate($startDate);
            } catch (\InvalidArgumentException $exception) {
                throw new HttpException(400, $exception->getMessage(), $exception);
            }
        }
        if ($endDate = $paramFetcher->get('createdDateTo')) {
            try {
                $request->endDate = DateTimeFactory::createDate($endDate);
                $request->endDate->setTime(23, 59, 59);
            } catch (\InvalidArgumentException $exception) {
                throw new HttpException(400, $exception->getMessage(), $exception);
            }
        }

        $statuses = $paramFetcher->get('statuses');
        if ($statuses) {
            $request->statuses = Helpers::typeCastAll('int', $statuses);
        }

        $request->organizationId = Helpers::typeCastNullable('int', $paramFetcher->get('organizationId'));
        $request->clientId = Helpers::typeCastNullable('int', $paramFetcher->get('clientId'));
        $request->number = $paramFetcher->get('number', true);
        $request->limit = Helpers::typeCastNullable('int', $paramFetcher->get('limit'));
        $request->offset = Helpers::typeCastNullable('int', $paramFetcher->get('offset'));
        $request->order = $paramFetcher->get('order', true);
        $request->direction = $paramFetcher->get('direction', true);
        $request->overdue = Helpers::typeCastNullable('bool', $paramFetcher->get('overdue'));
        $request->proforma = Helpers::typeCastNullable('bool', $paramFetcher->get('proforma'));

        $customAttributeKey = $paramFetcher->get('customAttributeKey', true);
        $customAttributeValue = $paramFetcher->get('customAttributeValue', true);

        if ($customAttributeKey && $customAttributeValue) {
            $request->matchByCustomAttribute($customAttributeKey, $customAttributeValue);
        } elseif ($customAttributeKey === null xor $customAttributeValue === null) {
            throw new ValidationHttpException(
                [],
                'You have to specify both customAttributeKey and customAttributeValue.'
            );
        }

        $invoices = $this->dataProvider->getInvoiceCollection($request);

        return $this->view(
            $this->mapper->reflectCollection($invoices)
        );
    }

    /**
     * @Post("/clients/{id}/invoices", name="client_invoice_add", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ParamConverter("invoiceMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function postAction(Client $client, InvoiceNewMap $invoiceMap, string $version): View
    {
        if ($client->isDeleted()) {
            throw new NotFoundHttpException('Client is archived. All actions are prohibited. You can only restore the client.');
        }

        if ($client->getIsLead()) {
            throw new ValidationHttpException([], 'This action is not possible, while the client is lead.');
        }

        $invoice = $this->financialFactory->createInvoice($client, new \DateTimeImmutable());
        $this->mapper->map($invoiceMap, $invoice);

        $validationGroups = [FinancialInterface::VALIDATION_GROUP_DEFAULT, FinancialInterface::VALIDATION_GROUP_API];
        $this->validator->validate($invoice, $this->mapper->getFieldsDifference(), null, $validationGroups);

        try {
            $this->facade->handleInvoiceCreate($invoice, $invoiceMap->applyCredit);
        } catch (TemplateRenderException | \Dompdf\Exception $exception) {
            throw new ValidationHttpException([], 'Invoice template contains errors and can\'t be safely used.');
        }

        return $this->routeRedirectViewWithData(
            $this->mapper->reflect($invoice),
            'api_invoice_get',
            [
                'version' => $version,
                'id' => $invoice->getId(),
            ]
        );
    }

    /**
     * @Patch("/invoices/{id}", name="invoice_edit", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ParamConverter("invoiceMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function patchAction(Invoice $invoice, InvoiceMap $invoiceMap): View
    {
        if ($invoice->getClient()->isDeleted()) {
            throw new NotFoundHttpException('Client is archived. All actions are prohibited. You can only restore the client.');
        }

        if (! $invoice->isEditable()) {
            throw new ValidationHttpException([], 'Only unpaid or zero invoice can be edited.');
        }

        if ($invoice->getInvoiceStatus() === Invoice::PAID) {
            $invoice->setInvoiceStatus(Invoice::UNPAID);
        }

        $this->mapper->map($invoiceMap, $invoice);
        $validationGroups = [FinancialInterface::VALIDATION_GROUP_DEFAULT, FinancialInterface::VALIDATION_GROUP_API];
        $this->validator->validate($invoice, $this->mapper->getFieldsDifference(), null, $validationGroups);

        try {
            $this->facade->handleInvoiceUpdate($invoice);
        } catch (TemplateRenderException | \Dompdf\Exception $exception) {
            throw new ValidationHttpException([], 'Invoice template contains errors and can\'t be safely used.');
        }

        return $this->view(
            $this->mapper->reflect($invoice)
        );
    }

    /**
     * @Patch("/invoices/{id}/send", name="invoice_send", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ViewHandler()
     * @Permission("edit")
     *
     * @throws ValidationHttpException
     */
    public function getAndSendInvoiceAction(Invoice $invoice): View
    {
        if ($invoice->getInvoiceStatus() === Invoice::DRAFT) {
            throw new ValidationHttpException(
                [
                    'invoiceStatus' => [
                        'Invoice drafts cannot be sent to client, they must be approved first.',
                    ],
                ]
            );
        }

        if (! $invoice->getClient()->hasBillingEmail()) {
            throw new HttpException(422, 'Email could not be sent, because client has no email set.');
        }

        $this->financialEmailSender->send(
            $invoice,
            $invoice->isProforma()
                ? NotificationTemplate::CLIENT_NEW_PROFORMA_INVOICE
                : NotificationTemplate::CLIENT_NEW_INVOICE
        );

        return $this->view(
            $this->mapper->reflect($invoice)
        );
    }

    /**
     * @Patch("/invoices/{id}/regenerate-pdf", name="invoice_regenerate_pdf", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ViewHandler()
     * @Permission("edit")
     *
     * @throws ValidationHttpException
     */
    public function regeneratePdfAction(Invoice $invoice): View
    {
        $this->get(PdfHandler::class)->saveInvoicePdf($invoice);

        return $this->view(
            $this->mapper->reflect($invoice)
        );
    }

    /**
     * @Patch(
     *     "/invoices/{id}/void",
     *     name="invoice_void",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("edit")
     */
    public function voidAction(Invoice $invoice): View
    {
        try {
            $this->facade->handleVoid($invoice);
        } catch (CannotDeleteProcessedProformaException $exception) {
            throw new HttpException(
                422,
                sprintf(
                    'Proforma invoice cannot be voided because invoice ID %d has been generated from it.',
                    $invoice->getGeneratedInvoice()->getId()
                )
            );
        }

        return $this->view(null, 200);
    }

    /**
     * @Delete(
     *     "/invoices/{id}",
     *     name="invoice_delete",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("edit")
     */
    public function deleteAction(Invoice $invoice): View
    {
        try {
            $this->facade->handleDelete($invoice);
        } catch (CannotDeleteProcessedProformaException $exception) {
            throw new HttpException(
                422,
                sprintf(
                    'Proforma invoice cannot be deleted because invoice ID %d has been generated from it.',
                    $invoice->getGeneratedInvoice()->getId()
                )
            );
        }

        return $this->view(null, 200);
    }
}
