<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Component\Validator\Validator;
use ApiBundle\Map\ServiceDeviceMap;
use ApiBundle\Mapper\ServiceDeviceMapper;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\ServiceController;
use AppBundle\DataProvider\ServiceDeviceDataProvider;
use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceDevice;
use AppBundle\Facade\ServiceDeviceFacade;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(ServiceController::class)
 */
class ServiceDeviceController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var ServiceDeviceFacade
     */
    private $facade;

    /**
     * @var ServiceDeviceMapper
     */
    private $mapper;

    /**
     * @var ServiceDeviceDataProvider
     */
    private $dataProvider;

    public function __construct(
        Validator $validator,
        ServiceDeviceFacade $facade,
        ServiceDeviceMapper $mapper,
        ServiceDeviceDataProvider $dataProvider
    ) {
        $this->validator = $validator;
        $this->facade = $facade;
        $this->mapper = $mapper;
        $this->dataProvider = $dataProvider;
    }

    /**
     * @Get(
     *     "/clients/services/service-devices/{id}",
     *     name="service_device_get",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(ServiceDevice $serviceDevice): View
    {
        return $this->view(
            $this->mapper->reflect($serviceDevice)
        );
    }

    /**
     * @Get(
     *     "/clients/services/{id}/service-devices",
     *     name="service_device_collection_get",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("view")
     */
    public function getCollectionAction(Service $service): View
    {
        $serviceDevices = $this->dataProvider->getAll($service);

        return $this->view(
            $this->mapper->reflectCollection($serviceDevices)
        );
    }

    /**
     * @Post(
     *     "/clients/services/{id}/service-devices",
     *     name="service_device_add",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ParamConverter("serviceDeviceMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function postAction(Service $service, ServiceDeviceMap $serviceDeviceMap, string $version): View
    {
        $this->notDeleted($service);

        $serviceDevice = new ServiceDevice();
        $this->facade->setDefaults($service, $serviceDevice);
        try {
            $this->mapper->map($serviceDeviceMap, $serviceDevice);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException(
                $exception->getMessage(),
                $exception->getPrevious(),
                $exception->getCode()
            );
        }

        $validationGroups = [
            ServiceDevice::VALIDATION_GROUP_SERVICE_DEVICE,
            ServiceDevice::VALIDATION_GROUP_API,
            'ServiceIp',
        ];
        $this->validator->validate(
            $serviceDevice,
            $this->mapper->getFieldsDifference(),
            null,
            $validationGroups
        );
        $this->facade->handleCreate($serviceDevice);

        return $this->routeRedirectViewWithData(
            $this->mapper->reflect($serviceDevice),
            'api_service_device_get',
            [
                'version' => $version,
                'id' => $serviceDevice->getId(),
            ]
        );
    }

    /**
     * @Patch(
     *     "/clients/services/service-devices/{id}",
     *     name="service_device_edit",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     *     )
     * @ParamConverter("serviceDeviceMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function patchAction(ServiceDevice $serviceDevice, ServiceDeviceMap $serviceDeviceMap): View
    {
        if (! $serviceDevice->getService()) {
            throw new NotFoundHttpException('Service device must be connected to service');
        }

        if ($serviceDevice->getService()->getClient()->isDeleted()) {
            throw new NotFoundHttpException(
                'Client is archived. All actions are prohibited. You can only restore the client.'
            );
        }

        $serviceDeviceBeforeUpdate = clone $serviceDevice;
        try {
            $this->mapper->map($serviceDeviceMap, $serviceDevice);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException(
                $exception->getMessage(),
                $exception->getPrevious(),
                $exception->getCode()
            );
        }
        $validationGroups = [ServiceDevice::VALIDATION_GROUP_SERVICE_DEVICE, ServiceDevice::VALIDATION_GROUP_API];
        $this->validator->validate($serviceDevice, $this->mapper->getFieldsDifference(), null, $validationGroups);

        $this->facade->handleUpdate($serviceDevice, $serviceDeviceBeforeUpdate);

        return $this->view(
            $this->mapper->reflect($serviceDevice)
        );
    }
}
