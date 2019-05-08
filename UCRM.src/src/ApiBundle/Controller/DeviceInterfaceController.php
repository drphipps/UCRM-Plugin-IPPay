<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Mapper\DeviceInterfaceMapper;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\DeviceInterfaceController as AppDeviceInterfaceController;
use AppBundle\DataProvider\DeviceInterfaceDataProvider;
use AppBundle\Entity\Device;
use AppBundle\Entity\DeviceInterface;
use AppBundle\Facade\DeviceInterfaceFacade;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\View\View;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(AppDeviceInterfaceController::class)
 */
class DeviceInterfaceController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var DeviceInterfaceFacade
     */
    private $facade;

    /**
     * @var DeviceInterfaceMapper
     */
    private $mapper;

    /**
     * @var DeviceInterfaceDataProvider
     */
    private $dataProvider;

    public function __construct(
        DeviceInterfaceFacade $facade,
        DeviceInterfaceMapper $mapper,
        DeviceInterfaceDataProvider $dataProvider
    ) {
        $this->facade = $facade;
        $this->mapper = $mapper;
        $this->dataProvider = $dataProvider;
    }

    /**
     * @Get(
     *     "/devices/device-interfaces/{id}",
     *     name="device_interface_get",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(DeviceInterface $deviceInterface): View
    {
        $this->notDeleted($deviceInterface);

        return $this->view(
            $this->mapper->reflect($deviceInterface)
        );
    }

    /**
     * @Get(
     *     "/devices/{id}/device-interfaces",
     *     name="device_interface_collection_get",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("view")
     */
    public function getCollectionAction(Device $device): View
    {
        $this->notDeleted($device);

        $deviceInterfaces = $this->dataProvider->getAllDeviceInterfaces($device);

        return $this->view(
            $this->mapper->reflectCollection($deviceInterfaces)
        );
    }
}
