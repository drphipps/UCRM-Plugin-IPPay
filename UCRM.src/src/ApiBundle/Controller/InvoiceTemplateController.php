<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Mapper\InvoiceTemplateMapper;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\InvoiceTemplateController as AppInvoiceTemplateController;
use AppBundle\DataProvider\InvoiceTemplateDataProvider;
use AppBundle\Entity\Financial\InvoiceTemplate;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\View\View;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(AppInvoiceTemplateController::class)
 */
class InvoiceTemplateController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var InvoiceTemplateMapper
     */
    private $mapper;

    /**
     * @var InvoiceTemplateDataProvider
     */
    private $dataProvider;

    public function __construct(
        InvoiceTemplateMapper $mapper,
        InvoiceTemplateDataProvider $dataProvider
    ) {
        $this->mapper = $mapper;
        $this->dataProvider = $dataProvider;
    }

    /**
     * @Get(
     *     "/invoice-templates/{id}",
     *     name="invoice_template_get",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(InvoiceTemplate $invoiceTemplate): View
    {
        $this->notDeleted($invoiceTemplate);

        return $this->view(
            $this->mapper->reflect($invoiceTemplate)
        );
    }

    /**
     * @Get(
     *     "/invoice-templates",
     *     name="invoice_template_collection_get",
     *     options={"method_prefix"=false}
     * )
     * @ViewHandler()
     * @Permission("view")
     */
    public function getCollectionAction(): View
    {
        $invoiceTemplates = $this->dataProvider->getAllInvoiceTemplates();

        return $this->view(
            $this->mapper->reflectCollection($invoiceTemplates)
        );
    }
}
