<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Component\Validator\Validator;
use ApiBundle\Map\ServiceSurchargeMap;
use ApiBundle\Mapper\ServiceSurchargeMapper;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\ServiceController;
use AppBundle\DataProvider\ServiceSurchargeDataProvider;
use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceSurcharge;
use AppBundle\Facade\ServiceSurchargeFacade;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(ServiceController::class)
 */
class ServiceSurchargeController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var ServiceSurchargeFacade
     */
    private $facade;

    /**
     * @var ServiceSurchargeMapper
     */
    private $mapper;

    /**
     * @var ServiceSurchargeDataProvider
     */
    private $dataProvider;

    public function __construct(
        Validator $validator,
        ServiceSurchargeFacade $facade,
        ServiceSurchargeMapper $mapper,
        ServiceSurchargeDataProvider $dataProvider
    ) {
        $this->validator = $validator;
        $this->facade = $facade;
        $this->mapper = $mapper;
        $this->dataProvider = $dataProvider;
    }

    /**
     * @Get(
     *     "/clients/services/service-surcharges/{id}",
     *     name="service_surcharge_get",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(ServiceSurcharge $serviceSurcharge): View
    {
        return $this->view(
            $this->mapper->reflect($serviceSurcharge)
        );
    }

    /**
     * @Get(
     *     "/clients/services/{id}/service-surcharges",
     *     name="service_surcharge_collection_get",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("view")
     */
    public function getCollectionAction(Service $service): View
    {
        $serviceSurcharges = $this->dataProvider->getAll($service);

        return $this->view(
            $this->mapper->reflectCollection($serviceSurcharges)
        );
    }

    /**
     * @Post(
     *     "/clients/services/{id}/service-surcharges",
     *     name="service_surcharge_add",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ParamConverter("serviceSurchargeMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function postAction(Service $service, ServiceSurchargeMap $serviceSurchargeMap, string $version): View
    {
        $this->notDeleted($service);

        $serviceSurcharge = new ServiceSurcharge();
        $this->facade->setServiceSurchargeDefaults($service, $serviceSurcharge);
        $this->mapper->map($serviceSurchargeMap, $serviceSurcharge);
        $this->validator->validate($serviceSurcharge, $this->mapper->getFieldsDifference());
        $this->facade->handleCreate($serviceSurcharge);

        return $this->routeRedirectViewWithData(
            $this->mapper->reflect($serviceSurcharge),
            'api_service_surcharge_get',
            [
                'version' => $version,
                'id' => $serviceSurcharge->getId(),
            ]
        );
    }

    /**
     * @Patch(
     *     "/clients/services/service-surcharges/{id}",
     *     name="service_surcharge_edit",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ParamConverter("serviceSurchargeMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function patchAction(ServiceSurcharge $serviceSurcharge, ServiceSurchargeMap $serviceSurchargeMap): View
    {
        if ($serviceSurcharge->getService()->getSupersededByService()) {
            throw new NotFoundHttpException('Editing is not allowed until the deferred change is applied.');
        }

        $serviceSurchargeBeforeUpdate = clone $serviceSurcharge;
        $this->mapper->map($serviceSurchargeMap, $serviceSurcharge);
        $this->validator->validate($serviceSurcharge, $this->mapper->getFieldsDifference());
        $this->facade->handleUpdate($serviceSurcharge, $serviceSurchargeBeforeUpdate);

        return $this->view(
            $this->mapper->reflect($serviceSurcharge)
        );
    }

    /**
     * @Delete(
     *     "/clients/services/service-surcharges/{id}",
     *     name="service_surcharge_delete",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("edit")
     */
    public function deleteAction(ServiceSurcharge $serviceSurcharge): View
    {
        $this->facade->handleDelete($serviceSurcharge);

        return $this->view(null, 200);
    }
}
