<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller\Mobile;

use ApiBundle\Controller\BaseController;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\InvoiceController;
use AppBundle\DataProvider\InvoiceSummaryDataProvider;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Util\Helpers;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Rest\Prefix("/mobile/invoices")
 * @Rest\NamePrefix("api_mobile_")
 * @PermissionControllerName(InvoiceController::class)
 */
class InvoiceListController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var InvoiceSummaryDataProvider
     */
    private $invoiceSummaryDataProvider;

    public function __construct(InvoiceSummaryDataProvider $invoiceSummaryDataProvider)
    {
        $this->invoiceSummaryDataProvider = $invoiceSummaryDataProvider;
    }

    /**
     * @Rest\Get("", name="invoice_list")
     * @Rest\View()
     * @Permission("view")
     * @Rest\QueryParam(
     *     name="statuses",
     *     requirements=@Assert\All(@Assert\Choice(Invoice::STATUSES)),
     *     strict=true,
     *     nullable=true,
     *     description="select only invoices in one of the given statuses"
     * )
     * @Rest\QueryParam(
     *     name="overdue",
     *     requirements="1",
     *     strict=true,
     *     nullable=true,
     *     description="select only overdue invoices"
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
     *     requirements="clientFirstName|clientlastName|createdDate",
     *     strict=true,
     *     nullable=true,
     *     description="order by (clientFirstName|clientlastName|createdDate)"
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
        $statuses = $paramFetcher->get('statuses');

        if ($statuses) {
            $statuses = Helpers::typeCastAll('int', $statuses);
        }

        $onlyOverdue = $paramFetcher->get('overdue') === '1';

        return $this->view(
            $this->invoiceSummaryDataProvider->getInvoices(
                $statuses,
                $onlyOverdue,
                Helpers::typeCastNullable('int', $paramFetcher->get('limit')),
                Helpers::typeCastNullable('int', $paramFetcher->get('offset')),
                $paramFetcher->get('order', true),
                $paramFetcher->get('direction', true)
            )
        );
    }

    /**
     * @Rest\Get("/counts-by-status", name="invoice_counts_by_status")
     * @Permission("view")
     */
    public function getCountsByStatusAction(): View
    {
        return $this->view(
            $this->invoiceSummaryDataProvider->getCountsByStatus()
        );
    }
}
