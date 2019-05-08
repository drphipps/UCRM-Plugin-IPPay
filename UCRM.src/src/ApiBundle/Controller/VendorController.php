<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Mapper\VendorMapper;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\VendorController as AppVendorController;
use AppBundle\Entity\Vendor;
use AppBundle\Facade\VendorFacade;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\View\View;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(AppVendorController::class)
 */
class VendorController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var VendorFacade
     */
    private $facade;

    /**
     * @var VendorMapper
     */
    private $mapper;

    public function __construct(VendorFacade $facade, VendorMapper $mapper)
    {
        $this->facade = $facade;
        $this->mapper = $mapper;
    }

    /**
     * @Get("/vendors/{id}", name="vendor_get", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(Vendor $vendor): View
    {
        return $this->view(
            $this->mapper->reflect($vendor)
        );
    }

    /**
     * @Get("/vendors", name="vendor_collection_get", options={"method_prefix"=false})
     * @ViewHandler()
     * @Permission("view")
     */
    public function getCollectionAction(): View
    {
        $vendors = $this->facade->getAllVendors();

        return $this->view(
            $this->mapper->reflectCollection($vendors)
        );
    }
}
