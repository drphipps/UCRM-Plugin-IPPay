<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Component\Validator\Validator;
use ApiBundle\Map\ClientLogMap;
use ApiBundle\Mapper\ClientLogMapper;
use ApiBundle\Request\ClientLogCollectionRequest;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\ClientController as AppClientController;
use AppBundle\DataProvider\ClientLogDataProvider;
use AppBundle\Entity\Client;
use AppBundle\Entity\ClientLog;
use AppBundle\Facade\ClientLogFacade;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Security\PermissionGrantedChecker;
use AppBundle\Security\SpecialPermission;
use AppBundle\Util\DateTimeFactory;
use AppBundle\Util\Helpers;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations\Delete;
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

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(AppClientController::class)
 */
class ClientLogController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var ClientLogDataProvider
     */
    private $dataProvider;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var ClientLogFacade
     */
    private $facade;

    /**
     * @var ClientLogMapper
     */
    private $mapper;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var PermissionGrantedChecker
     */
    private $permissionGrantedChecker;

    public function __construct(
        ClientLogDataProvider $dataProvider,
        EntityManagerInterface $em,
        ClientLogFacade $facade,
        ClientLogMapper $mapper,
        Validator $validator,
        PermissionGrantedChecker $permissionGrantedChecker
    ) {
        $this->dataProvider = $dataProvider;
        $this->em = $em;
        $this->facade = $facade;
        $this->mapper = $mapper;
        $this->validator = $validator;
        $this->permissionGrantedChecker = $permissionGrantedChecker;
    }

    /**
     * @Get("/client-logs/{id}", name="client_log_get", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(ClientLog $clientLog): View
    {
        return $this->view(
            $this->mapper->reflect($clientLog)
        );
    }

    /**
     * @Get("/client-logs", name="client_log_collection_get", options={"method_prefix"=false})
     * @ViewHandler()
     * @Permission("view")
     * @QueryParam(
     *     name="clientId",
     *     requirements="\d+",
     *     strict=true,
     *     nullable=true,
     *     description="client ID"
     * )
     * @QueryParam(
     *     name="createdDateFrom",
     *     requirements="\d{4}-\d{2}-\d{2}",
     *     strict=true,
     *     nullable=true,
     *     description="limit collection starting on date (including)"
     * )
     * @QueryParam(
     *     name="createdDateTo",
     *     requirements="\d{4}-\d{2}-\d{2}",
     *     strict=true,
     *     nullable=true,
     *     description="limit collection ending on date (including)"
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
     */
    public function getCollectionAction(ParamFetcherInterface $paramFetcher): View
    {
        $clientLogCollectionsRequest = new ClientLogCollectionRequest();

        if ($clientId = $paramFetcher->get('clientId')) {
            $client = $this->em->find(Client::class, $clientId);
            if (! $client) {
                throw new NotFoundHttpException('Client object not found.');
            }
            $clientLogCollectionsRequest->client = $client;
        }

        if ($startDate = $paramFetcher->get('createdDateFrom')) {
            try {
                $clientLogCollectionsRequest->startDate = DateTimeFactory::createDate($startDate);
            } catch (\InvalidArgumentException $exception) {
                throw new HttpException(400, $exception->getMessage(), $exception);
            }
        }

        if ($endDate = $paramFetcher->get('createdDateTo')) {
            try {
                $clientLogCollectionsRequest->endDate = DateTimeFactory::createDate($endDate)
                    ->setTime(23, 59, 59);
            } catch (\InvalidArgumentException $exception) {
                throw new HttpException(400, $exception->getMessage(), $exception);
            }
        }

        $clientLogCollectionsRequest->limit = Helpers::typeCastNullable('int', $paramFetcher->get('limit'));
        $clientLogCollectionsRequest->offset = Helpers::typeCastNullable('int', $paramFetcher->get('offset'));

        $clientLogs = $this->dataProvider->getAllLogs($clientLogCollectionsRequest);

        return $this->view(
            $this->mapper->reflectCollection($clientLogs)
        );
    }

    /**
     * @Post("/client-logs", name="client_log_add", options={"method_prefix"=false})
     * @ParamConverter("clientLogMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function postAction(ClientLogMap $clientLogMap, string $version): View
    {
        $clientLog = new ClientLog();
        $this->mapper->map($clientLogMap, $clientLog);
        $this->validator->validate($clientLog, $this->mapper->getFieldsDifference());
        $this->facade->handleNew($clientLog);

        return $this->routeRedirectViewWithData(
            $this->mapper->reflect($clientLog),
            'api_client_log_get',
            [
                'version' => $version,
                'id' => $clientLog->getId(),
            ]
        );
    }

    /**
     * @Patch("/client-logs/{id}", name="clien_log_edit", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ParamConverter("clientLogMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function patchAction(ClientLog $clientLog, ClientLogMap $clientLogMap): View
    {
        if (! $this->permissionGrantedChecker->isGrantedSpecial(SpecialPermission::CLIENT_LOG_EDIT)) {
            throw new HttpException(403, 'Access denied.');
        }

        $this->mapper->map($clientLogMap, $clientLog);
        $this->validator->validate($clientLog, $this->mapper->getFieldsDifference());
        $this->facade->handleEdit($clientLog);

        return $this->view(
            $this->mapper->reflect($clientLog)
        );
    }

    /**
     * @Delete(
     *     "/client-logs/{id}",
     *     name="clien_log_delete",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("edit")
     */
    public function deleteAction(ClientLog $clientLog): View
    {
        $this->facade->handleDelete($clientLog);

        return $this->view(null, 200);
    }
}
