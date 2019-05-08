<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Mapper\ProformaInvoiceTemplateMapper;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\ProformaInvoiceTemplateController as AppProformaInvoiceTemplateController;
use AppBundle\DataProvider\ProformaInvoiceTemplateDataProvider;
use AppBundle\Entity\Financial\ProformaInvoiceTemplate;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\View\View;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(AppProformaInvoiceTemplateController::class)
 */
class ProformaInvoiceTemplateController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var ProformaInvoiceTemplateMapper
     */
    private $mapper;

    /**
     * @var ProformaInvoiceTemplateDataProvider
     */
    private $dataProvider;

    public function __construct(
        ProformaInvoiceTemplateMapper $mapper,
        ProformaInvoiceTemplateDataProvider $dataProvider
    ) {
        $this->mapper = $mapper;
        $this->dataProvider = $dataProvider;
    }

    /**
     * @Get(
     *     "/proforma-invoice-templates/{id}",
     *     name="proforma_invoice_template_get",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(ProformaInvoiceTemplate $proformaInvoiceTemplate): View
    {
        $this->notDeleted($proformaInvoiceTemplate);

        return $this->view(
            $this->mapper->reflect($proformaInvoiceTemplate)
        );
    }

    /**
     * @Get(
     *     "/proforma-invoice-templates",
     *     name="proforma-invoice_template_collection_get",
     *     options={"method_prefix"=false}
     * )
     * @ViewHandler()
     * @Permission("view")
     */
    public function getCollectionAction(): View
    {
        $proformaInvoiceTemplates = $this->dataProvider->findAllTemplates();

        return $this->view(
            $this->mapper->reflectCollection($proformaInvoiceTemplates)
        );
    }
}
