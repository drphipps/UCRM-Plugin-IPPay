<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Mapper\OrganizationMapper;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\OrganizationController as AppOrganizationController;
use AppBundle\Entity\Organization;
use AppBundle\Facade\OrganizationFacade;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\View\View;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(AppOrganizationController::class)
 */
class OrganizationController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var OrganizationFacade
     */
    private $facade;

    /**
     * @var OrganizationMapper
     */
    private $mapper;

    public function __construct(OrganizationFacade $facade, OrganizationMapper $mapper)
    {
        $this->facade = $facade;
        $this->mapper = $mapper;
    }

    /**
     * @Get("/organizations/{id}", name="organization_get", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(Organization $organization): View
    {
        return $this->view(
            $this->mapper->reflect($organization)
        );
    }

    /**
     * @Get("/organizations", name="organization_collection_get", options={"method_prefix"=false})
     * @ViewHandler()
     * @Permission("view")
     */
    public function getCollectionAction(): View
    {
        $organizations = $this->facade->getAllOrganizations();

        return $this->view(
            $this->mapper->reflectCollection($organizations)
        );
    }
}
