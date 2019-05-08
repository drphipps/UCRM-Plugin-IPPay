<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Mapper\QuoteTemplateMapper;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\QuoteTemplateController as AppQuoteTemplateController;
use AppBundle\DataProvider\QuoteTemplateDataProvider;
use AppBundle\Entity\Financial\QuoteTemplate;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\View\View;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(AppQuoteTemplateController::class)
 */
class QuoteTemplateController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var QuoteTemplateMapper
     */
    private $mapper;

    /**
     * @var QuoteTemplateDataProvider
     */
    private $dataProvider;

    public function __construct(
        QuoteTemplateMapper $mapper,
        QuoteTemplateDataProvider $dataProvider
    ) {
        $this->mapper = $mapper;
        $this->dataProvider = $dataProvider;
    }

    /**
     * @Get(
     *     "/quote-templates/{id}",
     *     name="quote_template_get",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(QuoteTemplate $quoteTemplate): View
    {
        $this->notDeleted($quoteTemplate);

        return $this->view(
            $this->mapper->reflect($quoteTemplate)
        );
    }

    /**
     * @Get(
     *     "/quote-templates",
     *     name="quote_template_collection_get",
     *     options={"method_prefix"=false}
     * )
     * @ViewHandler()
     * @Permission("view")
     */
    public function getCollectionAction(): View
    {
        $quoteTemplates = $this->dataProvider->getAllQuoteTemplates();

        return $this->view(
            $this->mapper->reflectCollection($quoteTemplates)
        );
    }
}
