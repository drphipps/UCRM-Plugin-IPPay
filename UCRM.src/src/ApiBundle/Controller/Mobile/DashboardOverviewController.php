<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller\Mobile;

use ApiBundle\Controller\BaseController;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\DataProvider\DashboardDataProvider;
use AppBundle\Security\Permission;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @Rest\Prefix("/mobile/dashboard")
 * @Rest\NamePrefix("api_mobile_")
 */
class DashboardOverviewController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var DashboardDataProvider
     */
    private $dashboardDataProvider;

    public function __construct(DashboardDataProvider $dashboardDataProvider)
    {
        $this->dashboardDataProvider = $dashboardDataProvider;
    }

    /**
     * @Rest\Get("/overview", name="dashboard_overview")
     * @Rest\View()
     * @Permission("guest")
     */
    public function getOverviewAction(): View
    {
        $data = $this->dashboardDataProvider->getOverview();

        if (! $data) {
            throw new NotFoundHttpException('There is no organization in the database.');
        }

        return $this->view($data);
    }
}
