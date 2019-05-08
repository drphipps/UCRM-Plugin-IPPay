<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Mapper\SiteMapper;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\SiteController as AppSiteController;
use AppBundle\Entity\Site;
use AppBundle\Facade\SiteFacade;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Util\Helpers;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(AppSiteController::class)
 */
class SiteController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var SiteFacade
     */
    private $facade;

    /**
     * @var SiteMapper
     */
    private $mapper;

    public function __construct(SiteFacade $facade, SiteMapper $mapper)
    {
        $this->facade = $facade;
        $this->mapper = $mapper;
    }

    /**
     * @Get("/sites/{id}", name="site_get", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(Site $site): View
    {
        $this->notDeleted($site);

        return $this->view(
            $this->mapper->reflect($site)
        );
    }

    /**
     * @Get("/sites", name="site_collection_get", options={"method_prefix"=false})
     * @ViewHandler()
     * @Permission("view")
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
        $offset = Helpers::typeCastNullable('int', $paramFetcher->get('offset'));
        $limit = Helpers::typeCastNullable('int', $paramFetcher->get('limit'));

        $sites = $this->facade->getAllSites($limit, $offset);

        return $this->view(
            $this->mapper->reflectCollection($sites)
        );
    }
}
