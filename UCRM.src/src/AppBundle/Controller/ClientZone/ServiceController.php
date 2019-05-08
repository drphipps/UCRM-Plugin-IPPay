<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Controller\ClientZone;

use AppBundle\Component\NetFlow\NetFlowChartDataProvider;
use AppBundle\Component\NetFlow\TableDataProvider;
use AppBundle\Entity\Service;
use AppBundle\Security\Permission;
use AppBundle\Service\ServiceCalculations;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/client-zone/service")
 */
class ServiceController extends BaseController
{
    /**
     * @Route("/{id}", name="client_zone_service_show", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("guest")
     */
    public function showAction(Service $service): Response
    {
        $this->notDeleted($service);
        $this->verifyOwnership($service);
        $client = $this->getClient();

        return $this->render(
            'client_zone/service/show.html.twig',
            [
                'client' => $client,
                'hasNetFlowData' => $this->get(NetFlowChartDataProvider::class)->hasData($service),
                'dataUsagesPeriods' => $this->get(TableDataProvider::class)
                    ->getTableDataWithCorrectionByPeriod($service),
                'service' => $service,
                'serviceAddress' => implode(', ', $service->getAddress()),
                'serviceCalculation' => $this->get(ServiceCalculations::class),
            ]
        );
    }

    /**
     * @Route(
     *     "/{id}/chart-data",
     *     name="client_zone_service_chart_data",
     *     requirements={"id": "\d+"},
     *     options={"expose": true}
     * )
     * @Method("GET")
     * @Permission("guest")
     */
    public function getChartDataAction(Service $service): JsonResponse
    {
        $this->notDeleted($service);
        $this->verifyOwnership($service);

        /** @var NetFlowChartDataProvider $provider */
        $provider = $this->get(NetFlowChartDataProvider::class);

        return new JsonResponse(
            $this->get('jms_serializer')->serialize(
                [
                    'netflowChartData' => $provider->getChartDataForService($service),
                ],
                'json'
            ),
            200,
            [],
            true
        );
    }
}
