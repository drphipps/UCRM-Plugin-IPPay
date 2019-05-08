<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller\Mobile;

use ApiBundle\Controller\BaseController;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\ClientController;
use AppBundle\Controller\InvoiceController;
use AppBundle\Controller\ServiceController;
use AppBundle\DataProvider\ClientSummaryDataProvider;
use AppBundle\DataProvider\Request\ClientSummaryRequest;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Security\PermissionGrantedChecker;
use AppBundle\Util\Helpers;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;

/**
 * @Rest\Prefix("/mobile/clients")
 * @Rest\NamePrefix("api_mobile_")
 * @PermissionControllerName(ClientController::class)
 */
class ClientListController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var ClientSummaryDataProvider
     */
    private $clientSummaryDataProvider;

    /**
     * @var PermissionGrantedChecker
     */
    private $permissionGrantedChecker;

    public function __construct(
        ClientSummaryDataProvider $clientSummaryDataProvider,
        PermissionGrantedChecker $permissionGrantedChecker
    ) {
        $this->clientSummaryDataProvider = $clientSummaryDataProvider;
        $this->permissionGrantedChecker = $permissionGrantedChecker;
    }

    /**
     * @Rest\Get("", name="client_list")
     * @Rest\View()
     * @Permission("view")
     * @Rest\QueryParam(
     *     name="overdue",
     *     requirements="[01]",
     *     strict=true,
     *     nullable=true,
     *     description="filter clients with overdue invoice"
     * )
     * @Rest\QueryParam(
     *     name="suspended",
     *     requirements="[01]",
     *     strict=true,
     *     nullable=true,
     *     description="filter clients with suspended service"
     * )
     * @Rest\QueryParam(
     *     name="outage",
     *     requirements="[01]",
     *     strict=true,
     *     nullable=true,
     *     description="filter clients with ongoing outage"
     * )
     * @Rest\QueryParam(
     *     name="lead",
     *     requirements="[01]",
     *     strict=true,
     *     nullable=true,
     *     description="filter lead clients"
     * )
     * @Rest\QueryParam(
     *     name="limit",
     *     requirements="\d+",
     *     strict=true,
     *     nullable=true,
     *     description="max results limit"
     * )
     * @Rest\QueryParam(
     *     name="offset",
     *     requirements="\d+",
     *     strict=true,
     *     nullable=true,
     *     description="results offset"
     * )
     * @Rest\QueryParam(
     *     name="order",
     *     requirements="user\.firstName|user\.lastName|client\.registrationDate|client\.id",
     *     strict=true,
     *     nullable=true,
     *     description="order by (user.firstName|user.lastName|client.registrationDate|client.id)"
     * )
     * @Rest\QueryParam(
     *     name="direction",
     *     requirements="ASC|DESC",
     *     strict=true,
     *     nullable=true,
     *     description="direction of sort - ascending (ASC) or descending (DESC)"
     * )
     */
    public function getCollectionAction(ParamFetcherInterface $paramFetcher): View
    {
        $getRelatedServices = $this->permissionGrantedChecker->isGranted(Permission::VIEW, ServiceController::class);
        $getRelatedInvoices = $this->permissionGrantedChecker->isGranted(Permission::VIEW, InvoiceController::class);

        $request = new ClientSummaryRequest();
        $request->overdue = Helpers::typeCastNullable('bool', $paramFetcher->get('overdue'));
        $request->suspended = Helpers::typeCastNullable('bool', $paramFetcher->get('suspended'));
        $request->outage = Helpers::typeCastNullable('bool', $paramFetcher->get('outage'));
        $request->limit = Helpers::typeCastNullable('int', $paramFetcher->get('limit'));
        $request->offset = Helpers::typeCastNullable('int', $paramFetcher->get('offset'));
        $request->getRelatedServices = $getRelatedServices;
        $request->getRelatedInvoices = $getRelatedInvoices;
        $request->order = $paramFetcher->get('order', true);
        $request->direction = $paramFetcher->get('direction', true);
        $request->isLead = Helpers::typeCastNullable('bool', $paramFetcher->get('lead'));

        return $this->view(
            $this->clientSummaryDataProvider->getClients($request)
        );
    }

    /**
     * @Rest\Get("/counts-by-status", name="client_counts_by_status")
     * @Permission("view")
     */
    public function getCountsByStatusAction(): View
    {
        return $this->view(
            $this->clientSummaryDataProvider->getCountsByStatus()
        );
    }
}
