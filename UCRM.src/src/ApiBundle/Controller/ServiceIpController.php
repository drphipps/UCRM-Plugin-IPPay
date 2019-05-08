<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Component\Validator\Validator;
use ApiBundle\Map\ServiceIpMap;
use ApiBundle\Mapper\ServiceIpMapper;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\ServiceController;
use AppBundle\Entity\ServiceDevice;
use AppBundle\Entity\ServiceIp;
use AppBundle\Facade\ServiceIpFacade;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(ServiceController::class)
 */
class ServiceIpController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var ServiceIpFacade
     */
    private $facade;

    /**
     * @var ServiceIpMapper
     */
    private $mapper;

    public function __construct(
        Validator $validator,
        ServiceIpFacade $facade,
        ServiceIpMapper $mapper
    ) {
        $this->validator = $validator;
        $this->facade = $facade;
        $this->mapper = $mapper;
    }

    /**
     * @Get(
     *     "/clients/services/service-devices/service-ips/{id}",
     *     name="service_ip_get",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(ServiceIp $serviceIp): View
    {
        return $this->view(
            $this->mapper->reflect($serviceIp)
        );
    }

    /**
     * @Get(
     *     "/clients/services/service-devices/{id}/service-ips",
     *     name="service_ip_collection_get",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("view")
     */
    public function getCollectionAction(ServiceDevice $serviceDevice): View
    {
        $serviceIps = $serviceDevice->getServiceIps();

        return $this->view(
            $this->mapper->reflectCollection($serviceIps)
        );
    }

    /**
     * @Post(
     *     "/clients/services/service-devices/{id}/service-ips",
     *     name="service_ip_add",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ParamConverter("serviceIpMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function postAction(ServiceDevice $serviceDevice, ServiceIpMap $serviceIpMap, string $version): View
    {
        $serviceIp = new ServiceIp();
        $this->facade->setDefaults($serviceDevice, $serviceIp);
        $this->mapper->map($serviceIpMap, $serviceIp);
        $this->validator->validate($serviceIp, $this->mapper->getFieldsDifference());
        $this->facade->handleCreate($serviceIp);

        return $this->routeRedirectViewWithData(
            $this->mapper->reflect($serviceIp),
            'api_service_ip_get',
            [
                'version' => $version,
                'id' => $serviceIp->getId(),
            ]
        );
    }

    /**
     * @Delete(
     *     "/clients/services/service-devices/service-ips/{id}",
     *     name="service_ip_delete",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("edit")
     */
    public function deleteAction(ServiceIp $serviceIp): View
    {
        $this->facade->handleDelete($serviceIp);

        return $this->view(null, 200);
    }
}
