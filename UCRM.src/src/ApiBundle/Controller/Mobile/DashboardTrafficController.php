<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller\Mobile;

use ApiBundle\Controller\BaseController;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Component\NetFlow\NetFlowChartDataProvider;
use AppBundle\Controller\ReportDataUsageController;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;

/**
 * @Rest\Prefix("/mobile/dashboard")
 * @Rest\NamePrefix("api_mobile_")
 * @PermissionControllerName(ReportDataUsageController::class)
 */
class DashboardTrafficController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var NetFlowChartDataProvider
     */
    private $netFlowChartDataProvider;

    public function __construct(NetFlowChartDataProvider $netFlowChartDataProvider)
    {
        $this->netFlowChartDataProvider = $netFlowChartDataProvider;
    }

    /**
     * @Rest\Get("/last-week-traffic", name="dashboard_traffic")
     * @Rest\View()
     * @Permission("view")
     */
    public function getLastWeekTrafficAction(): View
    {
        return $this->view(
            $this->netFlowChartDataProvider->getChartDataForDashboard()
        );
    }
}
