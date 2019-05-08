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
use AppBundle\Controller\PaymentController;
use AppBundle\Controller\RefundController;
use AppBundle\DataProvider\ClientActivityDataProvider;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Security\PermissionGrantedChecker;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;

/**
 * @Rest\Prefix("/mobile/clients")
 * @Rest\NamePrefix("api_mobile_")
 * @PermissionControllerName(ClientController::class)
 */
class ClientActivityController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var ClientActivityDataProvider
     */
    private $clientActivityDataProvider;

    /**
     * @var PermissionGrantedChecker
     */
    private $permissionGrantedChecker;

    public function __construct(
        ClientActivityDataProvider $clientActivityDataProvider,
        PermissionGrantedChecker $permissionGrantedChecker
    ) {
        $this->clientActivityDataProvider = $clientActivityDataProvider;
        $this->permissionGrantedChecker = $permissionGrantedChecker;
    }

    /**
     * @Rest\Get("/{id}/activity", name="client_activity", requirements={"id": "\d+"})
     * @Rest\View()
     * @Permission("view")
     */
    public function getCollectionAction(int $id): View
    {
        $getInvoices = $this->permissionGrantedChecker->isGranted(Permission::VIEW, InvoiceController::class);
        $getPayments = $this->permissionGrantedChecker->isGranted(Permission::VIEW, PaymentController::class);
        $getRefunds = $this->permissionGrantedChecker->isGranted(Permission::VIEW, RefundController::class);

        $result = $this->clientActivityDataProvider->getActivity(
            $id,
            $getInvoices,
            $getPayments,
            $getRefunds
        );

        return $this->view($result);
    }
}
