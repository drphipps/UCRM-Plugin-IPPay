<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\DataProvider\DataUsageDataProvider;
use AppBundle\Facade\ReportDataUsageFacade;
use AppBundle\Grid\Report\DataUsageGridFactory;
use AppBundle\Grid\Report\DataUsageOverviewGridFactory;
use AppBundle\Security\Permission;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/reports/data-usage")
 */
class ReportDataUsageController extends BaseController
{
    /**
     * @Route("", name="report_data_usage_overview")
     * @Method({"GET"})
     * @Permission("view")
     */
    public function showOverviewAction(Request $request): Response
    {
        $grid = $this->get(DataUsageOverviewGridFactory::class)->create();
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'report/data_usage/show_overview.html.twig',
            [
                'isReportGenerated' => $this->get(ReportDataUsageFacade::class)->isReportGenerated(),
                'grid' => $grid,
                'periodDay' => DataUsageDataProvider::PERIOD_TODAY,
                'periodWeek' => DataUsageDataProvider::PERIOD_7_DAYS,
                'periodMonth' => DataUsageDataProvider::PERIOD_30_DAYS,
            ]
        );
    }

    /**
     * @Route("/generate", name="report_data_usage_generate")
     * @Permission("edit")
     * @CsrfToken()
     */
    public function generateCurrentReport(): Response
    {
        $this
            ->get(ReportDataUsageFacade::class)
            ->enqueueMessage($this->getUser());

        $this->addTranslatedFlash('success', 'Report was added to queue.');

        return $this->redirectToRoute('report_data_usage_overview');
    }

    /**
     * @Route(
     *     "/{period}",
     *     name="report_data_usage_since",
     *     requirements={
     *         "period": "day|week|month"
     *     }
     * )
     * @Method({"GET"})
     * @Permission("view")
     */
    public function showSinceAction(Request $request, string $period): Response
    {
        $grid = $this->get(DataUsageGridFactory::class)->create($period);
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'report/data_usage/show_since.html.twig',
            [
                'trafficGrid' => $grid,
                'period' => $period,
                'periodDay' => DataUsageDataProvider::PERIOD_TODAY,
                'periodWeek' => DataUsageDataProvider::PERIOD_7_DAYS,
                'periodMonth' => DataUsageDataProvider::PERIOD_30_DAYS,
            ]
        );
    }
}
