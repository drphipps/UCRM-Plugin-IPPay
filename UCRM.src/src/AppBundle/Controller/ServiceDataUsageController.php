<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\NetFlow\NetFlowChartDataProvider;
use AppBundle\Component\NetFlow\TableDataProvider;
use AppBundle\DataProvider\ServiceDataUsageProvider;
use AppBundle\Entity\Service;
use AppBundle\Exception\ServicePeriodNotFoundException;
use AppBundle\Facade\ServiceAccountingCorrectionFacade;
use AppBundle\Form\Data\ServiceDataUsageData;
use AppBundle\Form\ServiceDataUsageType;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Util\Formatter;
use AppBundle\Util\UnitConverter\BinaryConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/client/service")
 * @PermissionControllerName(ServiceController::class)
 */
class ServiceDataUsageController extends BaseController
{
    use ServiceControllerTrait;

    /**
     * @Route("/{id}/data-usage/edit/{timestamp}", name="service_data_usage_edit", requirements={"id": "\d+", "timestamp": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editDataUsage(Request $request, Service $service, int $timestamp): Response
    {
        $url = $this->generateUrl(
            'service_data_usage_edit',
            ['id' => $service->getId(), 'timestamp' => $timestamp]
        );

        $servicePeriods = $this->get(TableDataProvider::class)->getTableDataWithCorrectionByPeriod(
            $service
        );
        $currentPeriod = current($servicePeriods);
        $data = new ServiceDataUsageData();
        $data->date = (new \DateTime())->setTimestamp($timestamp);
        $data->period = (string) $timestamp;
        $data->download = round((new BinaryConverter($currentPeriod['download'], BinaryConverter::UNIT_BYTE))
            ->to(BinaryConverter::UNIT_GIBI), 4);
        $data->upload = round((new BinaryConverter($currentPeriod['upload'], BinaryConverter::UNIT_BYTE))
            ->to(BinaryConverter::UNIT_GIBI), 4);

        $form = $this->createForm(
            ServiceDataUsageType::class,
            $data,
            [
                'action' => $url,
                'servicePeriods' => $this->formatPeriodsForView($servicePeriods),
            ]
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $downloadInBytes = (int) (new BinaryConverter($data->download ?? 0.0, BinaryConverter::UNIT_GIBI))
                ->to(BinaryConverter::UNIT_BYTE);
            $uploadInBytes = (int) (new BinaryConverter($data->upload ?? 0.0, BinaryConverter::UNIT_GIBI))
                ->to(BinaryConverter::UNIT_BYTE);

            if ($data->editType === ServiceDataUsageData::EDIT_TYPE_DATE) {
                $this->get(ServiceAccountingCorrectionFacade::class)->setCorrectionForDay(
                    $service,
                    \DateTimeImmutable::createFromMutable($data->date),
                    $downloadInBytes,
                    $uploadInBytes
                );
            } elseif ($data->editType === ServiceDataUsageData::EDIT_TYPE_PERIOD) {
                try {
                    $timezone = new \DateTimeZone('UTC');
                    $this->get(ServiceAccountingCorrectionFacade::class)->setCorrectionForPeriod(
                        $service,
                        (new \DateTimeImmutable(sprintf('@%s', (int) $data->period), $timezone)),
                        $downloadInBytes,
                        $uploadInBytes
                    );
                } catch (ServicePeriodNotFoundException $e) {
                    throw $this->createNotFoundException();
                }
            }

            $this->addTranslatedFlash('success', 'Item has been saved.');

            $serviceRepository = $this->em->getRepository(Service::class);
            $this->invalidateTemplate(
                'service_charts',
                'client/services/components/view/charts.html.twig',
                [
                    'dataUsagesPeriods' => $this->get(TableDataProvider::class)->getTableDataWithCorrectionByPeriod(
                        $service
                    ),
                    'hasPingStatistics' => $serviceRepository->hasPingStatistics($service),
                    'hasSignalStatistics' => $serviceRepository->hasSignalStatistics($service),
                    'service' => $service,
                    'serviceDeviceIps' => $serviceRepository->getServiceDeviceIps($service),
                    'hasNetFlowData' => $this->get(NetFlowChartDataProvider::class)->hasData($service),
                ],
                true
            );

            return $this->createAjaxResponse();
        }

        return $this->render(
            'client/services/components/edit/data_usage_modal.html.twig',
            [
                'form' => $form->createView(),
                'service' => $service,
            ]
        );
    }

    /**
     * @Route("/{id}/data-usage/get/{timestamp}", name="service_data_usage_get", requirements={"id": "\d+", "timestamp": "\d+"}, options={"expose": true})
     * @Method("GET")
     * @Permission("view")
     */
    public function getDataUsage(Service $service, int $timestamp): Response
    {
        $timezone = new \DateTimeZone('UTC');
        $dateTime = (new \DateTimeImmutable(sprintf('@%s', $timestamp), $timezone));

        return $this->createAjaxResponse(
            (array) $this->get(ServiceDataUsageProvider::class)->getDataInDay(
                $service,
                $dateTime,
                BinaryConverter::UNIT_GIBI
            )
        );
    }

    /**
     * @Route("/{id}/data-usage-by-period/get/{timestamp}", name="service_data_usage_get_by_period", requirements={"id": "\d+", "timestamp": "\d+"}, options={"expose": true})
     * @Method("GET")
     * @Permission("view")
     */
    public function getDataUsageByPeriod(Service $service, int $timestamp): Response
    {
        $timezone = new \DateTimeZone('UTC');
        $firstDayOfPeriod = (new \DateTimeImmutable(sprintf('@%s', $timestamp), $timezone));

        $period = $this->get(TableDataProvider::class)->findPeriod($service, $firstDayOfPeriod);
        if (! $period) {
            throw $this->createNotFoundException();
        }

        return $this->createAjaxResponse(
            (array) $this->get(ServiceDataUsageProvider::class)->getDataInPeriod(
                $service,
                $period,
                BinaryConverter::UNIT_GIBI
            )
        );
    }

    /**
     * @param \DateTimeInterface[][] $servicePeriods
     */
    private function formatPeriodsForView(array $servicePeriods): array
    {
        $formatter = $this->get(Formatter::class);
        $servicePeriodsForChoice = [];
        foreach ($servicePeriods as $servicePeriod) {
            if (! $servicePeriod['invoicedFrom'] || ! $servicePeriod['invoicedTo']) {
                continue;
            }

            $servicePeriodsForChoice[sprintf(
                '%s - %s',
                $formatter->formatDate($servicePeriod['invoicedFrom'], Formatter::DEFAULT, Formatter::NONE),
                $formatter->formatDate($servicePeriod['invoicedTo'], Formatter::DEFAULT, Formatter::NONE)
            )] = $servicePeriod['invoicedFrom']->format('U');
        }

        return $servicePeriodsForChoice;
    }
}
