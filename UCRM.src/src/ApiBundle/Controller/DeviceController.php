<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Mapper\DeviceMapper;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\DeviceController as AppDeviceController;
use AppBundle\Entity\Device;
use AppBundle\Facade\DeviceFacade;
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
 * @PermissionControllerName(AppDeviceController::class)
 */
class DeviceController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var DeviceFacade
     */
    private $facade;

    /**
     * @var DeviceMapper
     */
    private $mapper;

    public function __construct(DeviceFacade $facade, DeviceMapper $mapper)
    {
        $this->facade = $facade;
        $this->mapper = $mapper;
    }

    /**
     * @Get(
     *     "/devices/{id}",
     *     name="device_get",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(Device $device): View
    {
        $this->notDeleted($device);

        return $this->view(
            $this->mapper->reflect($device)
        );
    }

    /**
     * @Get("/devices", name="device_collection_get", options={"method_prefix"=false})
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

        $devices = $this->facade->getAllDevices($limit, $offset);

        return $this->view(
            $this->mapper->reflectCollection($devices)
        );
    }
}
