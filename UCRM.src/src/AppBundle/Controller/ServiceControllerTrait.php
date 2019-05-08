<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\NetFlow\NetFlowChartDataProvider;
use AppBundle\Component\NetFlow\TableDataProvider;
use AppBundle\DataProvider\ServiceAirLinkDataProvider;
use AppBundle\Entity\Financial\InvoiceItemService;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\Financial\QuoteItemService;
use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceDevice;
use AppBundle\Entity\Tax;
use AppBundle\Form\ServiceNoteType;
use AppBundle\Service\Fee\EarlyTerminationDetector;
use AppBundle\Service\ServiceCalculations;
use AppBundle\Util\Formatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @property Container              $container
 * @property EntityManagerInterface $em
 */
trait ServiceControllerTrait
{
    private function invalidateTemplateServiceInformation(Service $service): void
    {
        /** @var BaseController $this */
        $invoiceExists = $this->em->getRepository(InvoiceItemService::class)->hasInvoice($service);
        $deletePossible = ! $invoiceExists && $service->getStatus() !== Service::STATUS_ACTIVE;

        $this->invalidateTemplate(
            'service_information',
            'client/services/components/view/information.html.twig',
            [
                'client' => $service->getClient(),
                'service' => $service,
                'noteForm' => $this->createForm(ServiceNoteType::class, $service)->createView(),
                'deletePossible' => $deletePossible,
                'showExpanded' => true,
                'availableTaxes' => $this->em->getRepository(Tax::class)->getAvailableTaxesForService($service),
                'serviceCalculation' => $this->container->get(ServiceCalculations::class),
                'useFullScreenEditForm' => ! $invoiceExists
                    && in_array($service->getStatus(), Service::FULL_EDITABLE_STATUSES, true),
                'useDeferEditForm' => in_array($service->getStatus(), Service::FULL_EDITABLE_STATUSES, true)
                    && $service->getStatus() !== Service::STATUS_QUOTED,
                'openQuoteExists' => $this->em->getRepository(QuoteItemService::class)
                    ->hasQuote($service, [Quote::STATUS_OPEN]),
            ]
        );
    }

    private function processTariffChangedSignal(
        Service $service,
        FormInterface $form,
        Request $request,
        bool $watchOut
    ): ?JsonResponse {
        /** @var BaseController $this */
        if (! $form->isSubmitted() || $request->request->get('signal') !== ServiceController::SIGNAL_TARIFF_CHANGED) {
            return null;
        }

        $this->invalidateTemplate(
            'service-tariffPeriod-widget',
            'client/services/components/edit/tariff_period_widget.html.twig',
            [
                'service' => $service,
                'form' => $form->createView(),
                'watchout' => $watchOut,
            ]
        );

        return $this->createAjaxResponse([], false);
    }

    private function processCheckEarlyTerminationFeeSignal(
        Service $service,
        FormInterface $form,
        Request $request
    ): ?JsonResponse {
        /** @var BaseController $this */
        if (! $form->isSubmitted() || $request->request->get('signal') !== ServiceController::SIGNAL_CHECK_EARLY_TERMINATION_FEE) {
            return null;
        }

        if ($form->getConfig()->hasOption('deferredChange') && $form->getConfig()->getOption('deferredChange')) {
            $confirmationNeeded = $service->getEarlyTerminationFeePrice() !== null;

            $confirmationMessage = $this->trans('Service upgrade / downgrade does not incur early termination fee. If you want to apply some fee you need to do it manually.');
        } else {
            $confirmationNeeded = $this->container->get(EarlyTerminationDetector::class)->shouldCreateEarlyTerminationFee($service);

            $confirmationMessage = sprintf(
                '%s<br>%s',
                $this->trans('Service Active From and Active To dates don\'t comply with the minimum contract length.'),
                $this->trans(
                    'Early termination fee (%price%) will be created for this service.',
                    [
                        '%price%' => htmlspecialchars(
                            $this->container->get(Formatter::class)->formatCurrency(
                                $service->getEarlyTerminationFeePrice() ?? 0,
                                $service->getClient()->getCurrencyCode()
                            ),
                            ENT_QUOTES
                        ),
                    ]
                )
                . ' '
                . $this->trans('Note that it may not be invoiced automatically.')
            );
        }

        return $this->createAjaxResponse(
            [
                'confirmationNeeded' => $confirmationNeeded,
                'confirmationMessage' => $confirmationMessage,
            ],
            false
        );
    }

    private function invalidateTemplateServiceDevice(Service $service): void
    {
        /** @var BaseController $this */
        $this->em->refresh($service);

        $deletedServiceDevices = $this->em->getRepository(ServiceDevice::class)
            ->getDeletedByClient($service->getClient());

        $this->invalidateTemplate(
            'service_devices',
            'client/services/components/view/service_devices.html.twig',
            [
                'service' => $service,
                'client' => $service->getClient(),
                'hasClientDeletedServiceDevices' => ! empty($deletedServiceDevices),
            ]
        );

        $this->invalidateTemplate(
            'deleted_service_devices',
            'client/components/view/deleted_service_devices.html.twig',
            [
                'client' => $service->getClient(),
                'deletedServiceDevices' => $deletedServiceDevices,
            ]
        );

        $this->invalidateTemplate(
            'service-map-container',
            'client/services/components/view/map.html.twig',
            [
                'service' => $service,
                'client' => $service->getClient(),
                'airLinkUrl' => $this->container->get(ServiceAirLinkDataProvider::class)->get($service),
            ]
        );

        $this->invalidateTemplateServiceCharts($service);
    }

    /**
     * @throws NotFoundHttpException
     */
    private function notDeferred(Service $entity): void
    {
        if ($entity->getSupersededByService()) {
            throw $this->createNotFoundException();
        }
    }

    private function invalidateTemplateServiceCharts(Service $service): void
    {
        $serviceRepository = $this->em->getRepository(Service::class);

        $this->invalidateTemplate(
            'service_charts',
            'client/services/components/view/charts.html.twig',
            [
                'service' => $service,
                'client' => $service->getClient(),
                'dataUsagesPeriods' => $this->container->get(TableDataProvider::class)->getTableDataWithCorrectionByPeriod($service),
                'hasNetFlowData' => $this->container->get(NetFlowChartDataProvider::class)->hasData($service),
                'serviceDeviceIps' => $serviceRepository->getServiceDeviceIps($service),
                'hasPingStatistics' => $serviceRepository->hasPingStatistics($service),
                'hasSignalStatistics' => $serviceRepository->hasSignalStatistics($service),
            ],
            true
        );
    }
}
