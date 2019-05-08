<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Component\Validator\Validator;
use ApiBundle\Map\ServiceCreateMap;
use ApiBundle\Map\ServiceEditMap;
use ApiBundle\Mapper\ServiceEditMapper;
use ApiBundle\Mapper\ServiceMapper;
use ApiBundle\Request\ServiceCollectionRequest;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\ServiceController as AppServiceController;
use AppBundle\DataProvider\ServiceDataProvider;
use AppBundle\Entity\Client;
use AppBundle\Entity\Service;
use AppBundle\Facade\ServiceFacade;
use AppBundle\Factory\ServiceFactory;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Service\ClientStatusUpdater;
use AppBundle\Service\ServiceStatusUpdater;
use AppBundle\Util\Helpers;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(AppServiceController::class)
 */
class ServiceController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var ServiceDataProvider
     */
    private $dataProvider;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var ServiceFacade
     */
    private $facade;

    /**
     * @var ServiceMapper
     */
    private $mapper;

    /**
     * @var ServiceEditMapper
     */
    private $editMapper;

    /**
     * @var ServiceStatusUpdater
     */
    private $serviceStatusUpdater;

    /**
     * @var ClientStatusUpdater
     */
    private $clientStatusUpdater;

    /**
     * @var ServiceFactory
     */
    private $serviceFactory;

    public function __construct(
        ServiceDataProvider $dataProvider,
        Validator $validator,
        ServiceFacade $facade,
        ServiceMapper $mapper,
        ServiceEditMapper $editMapper,
        ServiceStatusUpdater $serviceStatusUpdater,
        ClientStatusUpdater $clientStatusUpdater,
        ServiceFactory $serviceFactory
    ) {
        $this->dataProvider = $dataProvider;
        $this->validator = $validator;
        $this->facade = $facade;
        $this->mapper = $mapper;
        $this->editMapper = $editMapper;
        $this->serviceStatusUpdater = $serviceStatusUpdater;
        $this->clientStatusUpdater = $clientStatusUpdater;
        $this->serviceFactory = $serviceFactory;
    }

    /**
     * @Get(
     *     "/clients/services/{id}",
     *     name="service_get",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(Service $service): View
    {
        $this->notDeleted($service);

        return $this->view(
            $this->mapper->reflect($service)
        );
    }

    /**
     * @deprecated use getCollectionAction with clientId param
     *
     * @Get(
     *     "/clients/{id}/services",
     *     name="service_client_collection_get",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("view")
     */
    public function getClientCollectionAction(Client $client): View
    {
        if ($client->isDeleted()) {
            throw new NotFoundHttpException(
                'Client is archived. All actions are prohibited. You can only restore the client.'
            );
        }

        $request = new ServiceCollectionRequest();
        $request->clientId = $client->getId();

        $services = $this->dataProvider->getCollection($request);

        return $this->view(
            $this->mapper->reflectCollection($services)
        );
    }

    /**
     * @Get(
     *     "/clients/services",
     *     name="service_collection_get",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @QueryParam(
     *     name="organizationId",
     *     requirements="\d+",
     *     strict=true,
     *     nullable=true,
     *     description="organization ID"
     * )
     * @QueryParam(
     *     name="clientId",
     *     requirements="\d+",
     *     strict=true,
     *     nullable=true,
     *     description="client ID"
     * )
     * @QueryParam(
     *     name="statuses",
     *     requirements=@Assert\All(@Assert\Choice(Service::POSSIBLE_STATUSES)),
     *     strict=true,
     *     nullable=true,
     *     description="select only services in one of the given statuses"
     * )
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
     * @ViewHandler()
     * @Permission("view")
     */
    public function getCollectionAction(ParamFetcherInterface $paramFetcher): View
    {
        $request = new ServiceCollectionRequest();

        $statuses = $paramFetcher->get('statuses');
        if ($statuses) {
            $request->statuses = Helpers::typeCastAll('int', $statuses);
        }

        $request->organizationId = Helpers::typeCastNullable('int', $paramFetcher->get('organizationId'));
        $request->clientId = Helpers::typeCastNullable('int', $paramFetcher->get('clientId'));
        $request->limit = Helpers::typeCastNullable('int', $paramFetcher->get('limit'));
        $request->offset = Helpers::typeCastNullable('int', $paramFetcher->get('offset'));

        return $this->view(
            $this->mapper->reflectCollection(
                $this->dataProvider->getCollection($request)
            )
        );
    }

    /**
     * @Post(
     *     "/clients/{id}/services",
     *     name="service_add",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ParamConverter("serviceCreateMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function postAction(Client $client, ServiceCreateMap $serviceCreateMap, string $version): View
    {
        if ($client->isDeleted()) {
            throw new NotFoundHttpException(
                'Client is archived. All actions are prohibited. You can only restore the client.'
            );
        }

        $service = $this->serviceFactory->create($client);
        $this->mapper->map($serviceCreateMap, $service);

        $validationGroups = [Service::VALIDATION_GROUP_SERVICE, Service::VALIDATION_GROUP_INVOICE_PREVIEW];
        $this->validator->validate($service, $this->mapper->getFieldsDifference(), null, $validationGroups);
        $this->facade->handleCreate($service);

        return $this->routeRedirectViewWithData(
            $this->mapper->reflect($service),
            'api_service_get',
            [
                'version' => $version,
                'id' => $service->getId(),
            ]
        );
    }

    /**
     * @Patch("/clients/services/{id}", name="service_edit", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ParamConverter("serviceEditMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function patchAction(Service $service, ServiceEditMap $serviceEditMap): View
    {
        $this->validateIsNotDeletedOrDeferred($service);

        $serviceBeforeUpdate = clone $service;
        $this->editMapper->map($serviceEditMap, $service);
        $validationGroups = [Service::VALIDATION_GROUP_SERVICE, Service::VALIDATION_GROUP_INVOICE_PREVIEW];
        $this->validator->validate($service, $this->editMapper->getFieldsDifference(), null, $validationGroups);

        $this->facade->handleUpdate($service, $serviceBeforeUpdate);
        $this->serviceStatusUpdater->updateServices();
        $this->clientStatusUpdater->update();

        return $this->view(
            $this->mapper->reflect($service)
        );
    }

    /**
     * @Patch("/clients/services/{id}/geocode", name="service_geocode", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ViewHandler()
     * @Permission("edit")
     */
    public function geocodeAction(Service $service)
    {
        $this->validateIsNotDeletedOrDeferred($service);

        try {
            $this->facade->geocode($service);
        } catch (\RuntimeException $exception) {
            throw new HttpException(422, $exception->getMessage());
        }

        return $this->view(
            $this->mapper->reflect($service)
        );
    }

    private function validateIsNotDeletedOrDeferred(Service $service): void
    {
        if ($service->isDeleted()) {
            throw $this->createNotFoundException();
        }

        if ($service->getClient()->isDeleted()) {
            throw new NotFoundHttpException(
                'Client is archived. All actions are prohibited. You can only restore the client.'
            );
        }

        if ($service->getSupersededByService()) {
            throw new NotFoundHttpException('Editing is not allowed until the deferred change is applied.');
        }
    }
}
