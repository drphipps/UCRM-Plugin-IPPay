<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Controller;

use AppBundle\Component\Command\Suspension\ServiceSuspender;
use AppBundle\Component\NetFlow\NetFlowChartDataProvider;
use AppBundle\Component\NetFlow\TableDataProvider;
use AppBundle\Component\Ping\PingChartDataProvider;
use AppBundle\Component\WirelessStatistics\WirelessStatisticsChartDataProvider;
use AppBundle\DataProvider\InvoiceDataProvider;
use AppBundle\DataProvider\ServiceAirLinkDataProvider;
use AppBundle\DataProvider\ServiceDataProvider;
use AppBundle\Entity\Client;
use AppBundle\Entity\Country;
use AppBundle\Entity\EntityLog;
use AppBundle\Entity\Financial\InvoiceItemService;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\Financial\QuoteItemService;
use AppBundle\Entity\Option;
use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceDevice;
use AppBundle\Entity\Tariff;
use AppBundle\Entity\TariffPeriod;
use AppBundle\Entity\Tax;
use AppBundle\Facade\InvoiceFacade;
use AppBundle\Facade\QuoteFacade;
use AppBundle\Facade\ServiceDeviceFacade;
use AppBundle\Facade\ServiceFacade;
use AppBundle\Facade\ServiceSuspensionFacade;
use AppBundle\Factory\ServiceFactory;
use AppBundle\Form\ChooseClientType;
use AppBundle\Form\Data\ServiceDeleteData;
use AppBundle\Form\Data\ServiceEndData;
use AppBundle\Form\Data\ServicePostponeData;
use AppBundle\Form\Data\ServiceSuspendData;
use AppBundle\Form\Data\ServiceToServiceDeviceData;
use AppBundle\Form\ServiceChangeAddressType;
use AppBundle\Form\ServiceChangeTariffType;
use AppBundle\Form\ServiceContractType;
use AppBundle\Form\ServiceDeleteType;
use AppBundle\Form\ServiceEndType;
use AppBundle\Form\ServiceInvoiceInformationType;
use AppBundle\Form\ServiceNoteType;
use AppBundle\Form\ServicePostponeType;
use AppBundle\Form\ServiceSuspendType;
use AppBundle\Form\ServiceToServiceDeviceType;
use AppBundle\Form\ServiceType;
use AppBundle\Security\Permission;
use AppBundle\Service\ActionLogger;
use AppBundle\Service\ClientStatusUpdater;
use AppBundle\Service\Fee\EarlyTerminationDetector;
use AppBundle\Service\ServiceCalculations;
use AppBundle\Service\ServiceStatusUpdater;
use AppBundle\Util\Formatter;
use AppBundle\Util\Invoicing;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * @Route("/client/service")
 */
class ServiceController extends BaseController
{
    use ServiceControllerTrait;

    public const SIGNAL_TARIFF_CHANGED = 'tariff-changed';
    public const SIGNAL_CHECK_EARLY_TERMINATION_FEE = 'check-early-termination-fee';

    /**
     * @Route("/{id}", name="client_service_show", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("view")
     */
    public function showAction(Request $request, Service $service): Response
    {
        $this->notDeleted($service);

        $noteForm = $this->createForm(ServiceNoteType::class, $service);
        if ($noteFormResponse = $this->handleNoteForm($request, $service, $noteForm)) {
            return $noteFormResponse;
        }

        $nextPeriod = Invoicing::getMaxInvoicedPeriodService($service, new \DateTime('today'));

        $quotes = $this->em->getRepository(Quote::class)->getServiceQuotes($service);
        $openQuoteExists = false;
        foreach ($quotes as $quote) {
            if ($quote->getStatus() === Quote::STATUS_OPEN) {
                $openQuoteExists = true;

                break;
            }
        }

        $invoiceExists = $this->em->getRepository(InvoiceItemService::class)->hasInvoice($service);
        $deletePossible = ! $invoiceExists && $service->getStatus() !== Service::STATUS_ACTIVE;

        $serviceRepository = $this->em->getRepository(Service::class);
        $hasClientDeletedServiceDevices = $this->em->getRepository(ServiceDevice::class)
            ->hasClientDeleted($service->getClient());

        return $this->render(
            'client/services/show.html.twig',
            [
                'client' => $service->getClient(),
                'service' => $service,
                'deletePossible' => $deletePossible,
                'dataUsagesPeriods' => $this->get(TableDataProvider::class)->getTableDataWithCorrectionByPeriod(
                    $service
                ),
                'hasNetFlowData' => $this->get(NetFlowChartDataProvider::class)->hasData($service),
                'serviceStopReason' => $service->getStopReason(),
                'noteForm' => $noteForm->createView(),
                'nextInvoicingPeriodStart' => $nextPeriod['invoicedFrom'],
                'nextInvoicingPeriodEnd' => $nextPeriod['invoicedTo'],
                'useFullScreenEditForm' => ! $invoiceExists
                    && in_array($service->getStatus(), Service::FULL_EDITABLE_STATUSES, true),
                'useDeferEditForm' => in_array($service->getStatus(), Service::FULL_EDITABLE_STATUSES, true)
                    && $service->getStatus() !== Service::STATUS_QUOTED,
                'serviceDeviceIps' => $serviceRepository->getServiceDeviceIps($service),
                'hasPingStatistics' => $serviceRepository->hasPingStatistics($service),
                'hasSignalStatistics' => $serviceRepository->hasSignalStatistics($service),
                'availableTaxes' => $this->em->getRepository(Tax::class)->getAvailableTaxesForService($service),
                'serviceCalculation' => $this->get(ServiceCalculations::class),
                'hasClientDeletedServiceDevices' => $hasClientDeletedServiceDevices,
                'quotes' => $quotes,
                'openQuoteExists' => $openQuoteExists,
                'airLinkUrl' => $this->get(ServiceAirLinkDataProvider::class)->get($service),
                'showProformaInvoices' => $this->get(InvoiceDataProvider::class)->showProformaInvoices(),
            ]
        );
    }

    /**
     * @Route(
     *     "/{id}/chart-data",
     *     name="client_service_chart_data",
     *     requirements={"id": "\d+"},
     *     options={"expose": true}
     * )
     * @Method("GET")
     * @Permission("view")
     */
    public function getChartDataAction(Service $service): JsonResponse
    {
        $this->notDeleted($service);

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

    /**
     * @Route("/{id}/delete", name="client_service_delete", requirements={"id": "\d+"})
     * @Method({"POST", "GET"})
     * @Permission("edit")
     */
    public function deleteAction(Request $request, Service $service): Response
    {
        $this->notDeleted($service);
        $clientId = $service->getClient()->getId();

        $url = $this->generateUrl('client_service_delete', ['id' => $service->getId()]);
        $serviceDelete = new ServiceDeleteData();
        $form = $this->createForm(ServiceDeleteType::class, $serviceDelete, ['action' => $url]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (! in_array($service->getStatus(), Service::DELETABLE_STATUSES, true)) {
                $this->addTranslatedFlash('warning', 'Service must be deactivated first.');

                return $this->createAjaxRedirectResponse(
                    'client_service_show',
                    [
                        'id' => $service->getId(),
                    ]
                );
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->transactional(
                function () use ($service, $serviceDelete) {
                    if (! $serviceDelete->keepRelatedInvoices) {
                        [$deleted, $failed] = $this->get(InvoiceFacade::class)
                            ->handleDeleteMultipleByService($service);

                        if ($failed > 0) {
                            $this->addTranslatedFlash(
                                'warning',
                                'Some related invoices could not be deleted automatically.'
                            );
                        }
                    }
                    if (! $serviceDelete->keepRelatedQuotes) {
                        [$deleted, $failed] = $this->get(QuoteFacade::class)
                            ->handleDeleteMultipleByService($service);

                        if ($failed > 0) {
                            $this->addTranslatedFlash(
                                'warning',
                                'Some related quotes could not be deleted automatically.'
                            );
                        }
                    }
                    $this->get(ServiceFacade::class)->handleDelete($service, $serviceDelete->keepServiceDevices);
                }
            );

            $this->addTranslatedFlash('success', 'Service has been deleted.');

            return $this->createAjaxRedirectResponse(
                'client_show',
                [
                    'id' => $clientId,
                ]
            );
        }

        return $this->render(
            'client/services/components/edit/service_delete.html.twig',
            [
                'form' => $form->createView(),
                'service' => $service,
            ]
        );
    }

    /**
     * @Route("/{id}/end", name="client_service_end", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @CsrfToken()
     * @Permission("edit")
     */
    public function endAction(Service $service, Request $request): Response
    {
        $this->notDeleted($service);

        if ($service->getSupersededByService()) {
            $this->addTranslatedFlash(
                'error',
                'Ending the service is not allowed, because there is deferred change planned.'
            );

            return $this->createAjaxRedirectResponse(
                'client_service_show',
                [
                    'id' => $service->getId(),
                ]
            );
        }

        $earlyTerminationFeeCheckbox = $this->get(EarlyTerminationDetector::class)->shouldCreateEarlyTerminationFee(
            $service,
            new \DateTime('-1 day')
        );
        $data = new ServiceEndData();

        $form = $this->createForm(
            ServiceEndType::class,
            $data,
            [
                'action' => $this->generateUrl('client_service_end', ['id' => $service->getId()]),
                'earlyTerminationFeeCheckbox' => $earlyTerminationFeeCheckbox,
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(ServiceFacade::class)->handleEnd($service, $data->allowEarlyTerminationFee);

            $this->addTranslatedFlash('success', 'Service has been terminated.');

            return $this->createAjaxRedirectResponse(
                'client_service_show',
                [
                    'id' => $service->getId(),
                ]
            );
        }

        return $this->render(
            'client/services/components/edit/service_end.html.twig',
            [
                'form' => $form->createView(),
                'service' => $service,
            ]
        );
    }

    /**
     * @Route(
     *     "/{id}/update-gps-coordinates/{gpsLat}/{gpsLon}",
     *     name="update_gps_coordinates",
     *     requirements={"id": "\d+"},
     *     options={"expose": true},
     * )
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function updateGpsCoordinatesAction(Service $service, float $gpsLat, float $gpsLon): JsonResponse
    {
        $this->notDeleted($service);

        if ($service->getSupersededByService()) {
            $this->addTranslatedFlash(
                'error',
                $this->trans('Editing is not allowed until the deferred change is applied.')
            );

            return $this->createAjaxResponse();
        }

        $service->setAddressGpsLat($gpsLat);
        $service->setAddressGpsLon($gpsLon);

        $this->em->flush();

        $this->addTranslatedFlash('success', $this->trans('GPS coordinates updated.'));

        return $this->createAjaxResponse();
    }

    /**
     * @Route(
     *     "/{id}/edit-invoice-information",
     *     name="client_service_invoice_information_edit",
     *     requirements={"id": "\d+"}
     * )
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editServiceInvoiceInformationAction(Request $request, Service $service): Response
    {
        $this->notDeleted($service);
        $this->notDeferred($service);

        $url = $this->generateUrl('client_service_invoice_information_edit', ['id' => $service->getId()]);
        $invoiceExists = $this->em->getRepository(InvoiceItemService::class)->hasInvoice($service);
        $form = $this->createForm(
            ServiceInvoiceInformationType::class,
            $service,
            [
                'action' => $url,
                'enableActiveFrom' => in_array(
                        $service->getStatus(),
                        [Service::STATUS_PREPARED, Service::STATUS_PREPARED_BLOCKED, Service::STATUS_QUOTED],
                        true
                    )
                    || (
                        $service->getStatus() === Service::STATUS_ACTIVE
                        && ! $invoiceExists
                    ),
                'periodChangeDisabled' => $invoiceExists,
                'attr' => [
                    'data-signal-check-early-termination-fee' => self::SIGNAL_CHECK_EARLY_TERMINATION_FEE,
                ],
            ]
        );

        $serviceBeforeUpdate = clone $service;
        $form->handleRequest($request);

        if ($response = $this->processCheckEarlyTerminationFeeSignal($service, $form, $request)) {
            return $response;
        }

        if ($response = $this->processTariffChangedSignal($service, $form, $request, true)) {
            return $response;
        }

        if ($form->isSubmitted() && $form->isValid()) {
            if ($invoiceExists) {
                // Tariff period change is not allowed, force-select the same period as before.
                $service->setTariffPeriod($service->getTariff()->getPeriodByPeriod($serviceBeforeUpdate->getTariffPeriod()->getPeriod()));
            }

            $this->get(ServiceFacade::class)->handleUpdate($service, $serviceBeforeUpdate);
            $this->get(ServiceStatusUpdater::class)->updateServices();
            $this->get(ClientStatusUpdater::class)->update();

            $nextPeriod = Invoicing::getMaxInvoicedPeriodService($service, new \DateTime('today'));

            $this->invalidateTemplateServiceInformation($service);

            $this->invalidateTemplate(
                'service_invoice_information',
                'client/services/components/view/invoice_information.html.twig',
                [
                    'service' => $service,
                    'client' => $service->getClient(),
                    'nextInvoicingPeriodStart' => $nextPeriod['invoicedFrom'],
                    'nextInvoicingPeriodEnd' => $nextPeriod['invoicedTo'],
                    'useFullScreenEditForm' => false,
                    'useDeferEditForm' => in_array($service->getStatus(), Service::FULL_EDITABLE_STATUSES, true)
                        && $service->getStatus() !== Service::STATUS_QUOTED,
                ]
            );

            $this->addTranslatedFlash('success', $this->trans('Service has been updated.'));

            return $this->createAjaxResponse();
        }

        return $this->render(
            'client/services/components/edit/invoice_information_edit.html.twig',
            [
                'service' => $service,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}/edit-contract", name="client_service_contract_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editServiceContractAction(Request $request, Service $service): Response
    {
        $this->notDeleted($service);
        $this->notDeferred($service);

        $hasSupersededService = $this->get(ServiceDataProvider::class)->hasSupersededService($service);

        $url = $this->generateUrl('client_service_contract_edit', ['id' => $service->getId()]);
        $options = [
            'action' => $url,
            'enableSetupFee' => ! $hasSupersededService && $service->canSetSetupFee(),
        ];
        $form = $this->createForm(
            ServiceContractType::class,
            $service,
            $options
        );

        $serviceBeforeUpdate = clone $service;
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(ServiceFacade::class)->handleSaveContract(
                $service,
                $serviceBeforeUpdate,
                $options['enableSetupFee'] ? $form->get('setupFeePrice')->getData() : null
            );

            $this->invalidateTemplate(
                'service-contract',
                'client/services/components/view/contract.html.twig',
                [
                    'service' => $service,
                    'client' => $service->getClient(),
                ]
            );
            $this->addTranslatedFlash('success', $this->trans('Contract has been saved.'));

            return $this->createAjaxResponse();
        }

        return $this->render(
            'client/services/components/edit/contract_edit.html.twig',
            [
                'form' => $form->createView(),
                'service' => $service,
            ]
        );
    }

    /**
     * @Route(
     *     "/get-discount-dates/{id}/{fromDate}",
     *     name="get_discount_dates",
     *     requirements={"period_id": "\d+"},
     *     options={"expose"=true},
     *     defaults={"fromDate" = null},
     *     requirements={"id": "\d+"})
     * )
     * @Method("GET")
     * @Permission("view")
     */
    public function getDiscountDatesAction(
        Request $request,
        TariffPeriod $tariffPeriod,
        string $fromDate = null
    ): JsonResponse {
        $serviceId = (int) $request->get('service');
        if ($serviceId) {
            $service = $this->em->find(Service::class, $serviceId);
        } else {
            $service = new Service();
            $service->setInvoicingStart(new \DateTime('midnight'));
        }
        if (null !== $request->get('periodStartDay')) {
            $periodStartDay = (int) $request->get('periodStartDay');
            $service->setInvoicingPeriodStartDay($periodStartDay);
        }

        $service->setTariffPeriod($tariffPeriod);
        $fromDate = $fromDate ? new \DateTime($fromDate) : null;

        list($discountFromChoices, $discountToChoices) = Invoicing::getInvoicedPeriodsForm(
            $service,
            $fromDate,
            $this->get(Formatter::class)
        );

        array_unshift($discountFromChoices, $this->trans('Make a choice.'));
        array_unshift($discountToChoices, $this->trans('Make a choice.'));

        return new JsonResponse(
            [
                'periodId' => $tariffPeriod->getId(),
                'discountFromChoices' => $discountFromChoices,
                'discountToChoices' => $discountToChoices,
            ]
        );
    }

    /**
     * @Route("/{id}/change-service-plan", name="client_service_change_tariff", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editServiceTariffAction(Request $request, Service $service): Response
    {
        $this->notDeleted($service);
        $this->notDeferred($service);

        $url = $this->generateUrl('client_service_change_tariff', ['id' => $service->getId()]);
        $service->setDiscountInvoiceLabel($service->getDiscountInvoiceLabel() ?: $this->trans('Discount'));
        $invoiceExists = $this->em->getRepository(InvoiceItemService::class)->hasInvoice($service);
        $form = $this->createForm(
            ServiceChangeTariffType::class,
            $service,
            [
                'periodChangeDisabled' => $invoiceExists,
                'action' => $url,
            ]
        );
        $serviceBeforeUpdate = clone $service;
        $form->handleRequest($request);

        if ($response = $this->processTariffChangedSignal($service, $form, $request, true)) {
            return $response;
        }

        list($discountFromChoices, $discountToChoices) = Invoicing::getInvoicedPeriodsForm(
            $service,
            null,
            $this->get(Formatter::class)
        );

        if ($form->isSubmitted() && $form->isValid()) {
            if ($invoiceExists) {
                // Tariff period change is not allowed, force-select the same period as before.
                $service->setTariffPeriod($service->getTariff()->getPeriodByPeriod($serviceBeforeUpdate->getTariffPeriod()->getPeriod()));
            }

            if (
                $service->getDiscountFrom()
                && ! in_array($service->getDiscountFrom()->format('Y-m-d'), array_keys($discountFromChoices))
            ) {
                $service->setDiscountFrom(null);
            }

            if (
                $service->getDiscountTo()
                && ! in_array($service->getDiscountTo()->format('Y-m-d'), array_keys($discountToChoices))
            ) {
                $service->setDiscountTo(null);
            }

            $this->get(ServiceFacade::class)->handleUpdate($service, $serviceBeforeUpdate);

            $nextPeriod = Invoicing::getMaxInvoicedPeriodService($service, new \DateTime('today'));

            $this->invalidateTemplateServiceInformation($service);

            $this->invalidateTemplate(
                'service_invoice_information',
                'client/services/components/view/invoice_information.html.twig',
                [
                    'service' => $service,
                    'client' => $service->getClient(),
                    'nextInvoicingPeriodStart' => $nextPeriod['invoicedFrom'],
                    'nextInvoicingPeriodEnd' => $nextPeriod['invoicedTo'],
                    'useFullScreenEditForm' => false,
                    'useDeferEditForm' => in_array($service->getStatus(), Service::FULL_EDITABLE_STATUSES, true)
                        && $service->getStatus() !== Service::STATUS_QUOTED,
                ]
            );
            $this->addTranslatedFlash('success', $this->trans('Service plan has been saved.'));

            return $this->createAjaxResponse();
        }

        array_unshift($discountFromChoices, $this->trans('Make a choice.'));
        array_unshift($discountToChoices, $this->trans('Make a choice.'));

        return $this->render(
            'client/services/components/edit/change_tariff.html.twig',
            [
                'form' => $form->createView(),
                'service' => $service,
                'discountFromChoices' => $discountFromChoices,
                'discountToChoices' => $discountToChoices,
            ]
        );
    }

    /**
     * @Route("/{id}/change-address", name="client_service_address_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editServiceAddressAction(Request $request, Service $service): Response
    {
        $this->notDeleted($service);
        $this->notDeferred($service);

        $url = $this->generateUrl('client_service_address_edit', ['id' => $service->getId()]);
        $form = $this->createForm(ServiceChangeAddressType::class, $service, ['action' => $url]);
        $serviceBeforeUpdate = clone $service;
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(ServiceFacade::class)->handleUpdate($service, $serviceBeforeUpdate);

            $this->invalidateTemplate(
                'service-map-container',
                'client/services/components/view/map.html.twig',
                [
                    'service' => $service,
                    'client' => $service->getClient(),
                ]
            );
            $this->addTranslatedFlash('success', $this->trans('Address has been saved.'));

            return $this->createAjaxResponse();
        }

        return $this->render(
            'client/services/components/edit/address_edit.html.twig',
            [
                'form' => $form->createView(),
                'service' => $service,
                'fccReportsEnabled' => in_array(
                    $service->getClient()->getOrganization()->getCountry()->getId(),
                    Country::FCC_COUNTRIES,
                    true
                ),
            ]
        );
    }

    /**
     * @Route(
     *     "/service-device/{id}/ping-chart-data/{longTerm}",
     *     name="client_service_device_ping_chart_data",
     *     requirements={"id": "\d+"},
     *     options={"expose":true}
     * )
     * @Method("GET")
     * @Permission("view")
     */
    public function getPingChartDataAction(ServiceDevice $device, bool $longTerm = false): JsonResponse
    {
        $provider = $this->get(PingChartDataProvider::class);

        return new JsonResponse(
            $provider->getDataForDevice(
                PingChartDataProvider::TYPE_SERVICE,
                $device->getId(),
                $longTerm
                    ? PingChartDataProvider::RESOLUTION_LONG_TERM
                    : PingChartDataProvider::RESOLUTION_SHORT_TERM
            )
        );
    }

    /**
     * @Route(
     *     "/service-device/{id}/wireless-statistics-chart-data/{longTerm}",
     *     name="client_service_device_wireless_statistics_chart_data",
     *     requirements={"id": "\d+"},
     *     options={"expose":true}
     * )
     * @Method("GET")
     * @Permission("view")
     */
    public function getWirelessStatisticsChartDataAction(ServiceDevice $device, bool $longTerm = false): JsonResponse
    {
        $provider = $this->get(WirelessStatisticsChartDataProvider::class);

        return new JsonResponse(
            $provider->getDataForDevice(
                WirelessStatisticsChartDataProvider::TYPE_SERVICE,
                $device->getId(),
                $longTerm
                    ? WirelessStatisticsChartDataProvider::RESOLUTION_LONG_TERM
                    : WirelessStatisticsChartDataProvider::RESOLUTION_SHORT_TERM
            )
        );
    }

    /**
     * @Route("/{id}/cancel-suspend", name="client_service_cancel_suspend", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function cancelSuspendAction(Service $service): Response
    {
        $this->notDeleted($service);

        $this->get(ServiceSuspensionFacade::class)
            ->manuallyCancelSuspension($service);

        $this->get(ServiceStatusUpdater::class)->updateServices();
        $this->get(ClientStatusUpdater::class)->update();

        $this->addTranslatedFlash('success', 'Suspend has been canceled.');

        return $this->redirectToRoute(
            'client_service_show',
            [
                'id' => $service->getId(),
            ]
        );
    }

    /**
     * @Route("/{id}/postpone", name="client_service_postpone", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function postponeAction(Request $request, Service $service): Response
    {
        $this->notDeleted($service);

        $url = $this->generateUrl('client_service_postpone', ['id' => $service->getId()]);
        $data = new ServicePostponeData();
        $defaultSuspendedFrom = new \DateTime('tomorrow midnight');
        $data->postponeUntil = $service->getSuspendedFrom()
            ? max($defaultSuspendedFrom, $service->getSuspendedFrom())
            : $defaultSuspendedFrom;
        $form = $this->createForm(
            ServicePostponeType::class,
            $data,
            [
                'action' => $url,
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(ServiceSuspensionFacade::class)
                ->postponeServiceByAdmin($service, $data->postponeUntil);

            $this->get(ServiceStatusUpdater::class)->updateServices();
            $this->get(ClientStatusUpdater::class)->update();

            $this->invalidateTemplate(
                'service-actions',
                'client/services/components/view/actions.html.twig',
                [
                    'client' => $service->getClient(),
                    'service' => $service,
                    'openQuoteExists' => $this->em->getRepository(QuoteItemService::class)
                        ->hasQuote($service, [Quote::STATUS_OPEN]),
                ]
            );

            $this->invalidateTemplateServiceInformation($service);

            $this->addTranslatedFlash('success', $this->trans('Postpone has been saved.'));

            $logMessage['logMsg'] = [
                'message' => 'Suspension of service %s postponed.',
                'replacements' => $service->getName(),
            ];
            $logger = $this->container->get(ActionLogger::class);
            $logger->log($logMessage, $this->getUser(), $service->getClient(), EntityLog::POSTPONE);

            return $this->createAjaxResponse();
        }

        return $this->render(
            'client/services/components/edit/postpone_edit.html.twig',
            [
                'form' => $form->createView(),
                'service' => $service,
            ]
        );
    }

    /**
     * @Route("/{id}/suspend", name="client_service_suspend", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function suspendAction(Request $request, Service $service): Response
    {
        $this->notDeleted($service);

        $url = $this->generateUrl('client_service_suspend', ['id' => $service->getId()]);
        $data = new ServiceSuspendData();
        $data->stopReason = $service->getStopReason();
        $form = $this->createForm(ServiceSuspendType::class, $data, ['action' => $url]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(ServiceSuspensionFacade::class)
                ->suspendService($service, $data->stopReason, new \DateTimeImmutable());

            $this->get(ServiceStatusUpdater::class)->updateServices();
            $this->get(ClientStatusUpdater::class)->update();

            $message['logMsg'] = [
                'message' => 'Service %s has been suspended.',
                'replacements' => $service->getName(),
            ];
            $logger = $this->container->get(ActionLogger::class);
            $logger->log($message, $this->getUser(), $service->getClient(), EntityLog::SUSPEND);

            $this->addTranslatedFlash('success', 'Suspend has been saved.');

            return $this->createAjaxRedirectResponse(
                'client_service_show',
                [
                    'id' => $service->getId(),
                ]
            );
        }

        return $this->render(
            'client/services/components/edit/suspend_edit.html.twig',
            [
                'form' => $form->createView(),
                'service' => $service,
                'hasCancelledSuspension' => (bool) $this->em->getRepository(InvoiceItemService::class)
                    ->getInvoicesWithCancelledSuspension(
                        $this->getOption(Option::STOP_SERVICE_DUE),
                        $this->getOption(Option::STOP_SERVICE_DUE_DAYS),
                        $service
                    ),
            ]
        );
    }

    /**
     * @Route("/{id}/suspend-again", name="client_service_suspend_again", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function suspendAgainAction(Service $service): Response
    {
        $this->notDeleted($service);

        if (! $this->getOption(Option::SUSPEND_ENABLED)) {
            $this->addTranslatedFlash('error', 'Suspend feature is disabled in the system settings.');

            return $this->createAjaxRedirectResponse(
                'client_service_show',
                [
                    'id' => $service->getId(),
                ]
            );
        }

        $invoices = $this->em->getRepository(InvoiceItemService::class)
            ->getInvoicesWithCancelledSuspension(
                $this->getOption(Option::STOP_SERVICE_DUE),
                $this->getOption(Option::STOP_SERVICE_DUE_DAYS),
                $service
            );

        if (! $invoices) {
            $this->addTranslatedFlash('error', 'There are no invoices, that can cause service suspension.');
        } else {
            $this->get(InvoiceFacade::class)->restoreCanCauseSuspension($invoices);

            if ($this->get(ServiceSuspender::class)->suspend($service)) {
                $this->addTranslatedFlash('success', 'Service has been suspended.');
            } else {
                $this->addTranslatedFlash('error', 'Service could not be suspended.');
            }
        }

        return $this->createAjaxRedirectResponse(
            'client_service_show',
            [
                'id' => $service->getId(),
            ]
        );
    }

    /**
     * @Route(
     *     "/invoice-preview/{id}",
     *     name="client_service_invoice_preview",
     *     requirements={"id": "\d+"},
     *     options={"expose": true}
     * )
     * @Method("POST")
     * @Permission("edit")
     */
    public function getInvoicePreviewAction(Request $request, Client $client): JsonResponse
    {
        $this->notDeleted($client);

        $service = $this->get(ServiceFactory::class)->create($client);
        $form = $this->createForm(
            ServiceType::class,
            $service,
            [
                'validation_groups' => [
                    Service::VALIDATION_GROUP_INVOICE_PREVIEW,
                ],
                'allow_extra_fields' => true,
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formatter = $this->get(Formatter::class);
            /** @var array $period */
            $period = Invoicing::getMaxInvoicedPeriodService($service, new \DateTime('today'));

            if (! $period['invoicedFrom']) {
                return new JsonResponse(null, 422);
            }

            $period['invoicedFrom'] = new \DateTime($period['invoicedFrom']->format('Y-m-d'));
            $period['invoicedTo'] = new \DateTime($period['invoicedTo']->format('Y-m-d'));

            $from = $formatter->formatDate($period['invoicedFrom'], Formatter::DEFAULT, Formatter::NONE);
            $to = $formatter->formatDate($period['invoicedTo'], Formatter::DEFAULT, Formatter::NONE);

            $price = $this->get(ServiceCalculations::class)->getTotalPrice(
                $service,
                $period['invoicedFrom'],
                $period['invoicedTo']
            );
            $price = $formatter->formatCurrency(
                $price,
                $client->getCurrencyCode(),
                $client->getOrganization()->getLocale()
            );

            $today = new \DateTime('today midnight');
            $createdDate = Invoicing::getNextInvoicingDay($service, $period);
            if ($createdDate < $today) {
                $createdDate = $today;
            }
            $dueDate = clone $createdDate;
            $dueDate->modify(
                sprintf(
                    '+%d days',
                    $client->getInvoiceMaturityDays() ?? $client->getOrganization()->getInvoiceMaturityDays()
                )
            );

            return new JsonResponse(
                [
                    'price' => $price,
                    'period' => sprintf('%s - %s', $from, $to),
                    'createdDate' => $formatter->formatDate($createdDate, Formatter::DEFAULT, Formatter::NONE),
                    'dueDate' => $formatter->formatDate($dueDate, Formatter::DEFAULT, Formatter::NONE),
                ]
            );
        }

        return new JsonResponse(null, 422);
    }

    /**
     * @Route(
     *     "/service-plan/{id}/json",
     *     name="client_service_tariff_json",
     *     requirements={"id": "\d+"},
     *     options={"expose": true}
     * )
     * @Method("GET")
     * @Permission("view")
     */
    public function getTariffJsonAction(Tariff $tariff): JsonResponse
    {
        $this->notDeleted($tariff);

        return new JsonResponse(
            [
                'taxable' => $tariff->getTaxable(),
                'invoiceLabel' => $tariff->getInvoiceLabelOrName(),
            ]
        );
    }

    /**
     * @Route("/{id}/attach-service-device", name="client_service_attach_service_device", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function attachServiceDeviceAction(Request $request, Service $service): Response
    {
        $url = $this->generateUrl('client_service_attach_service_device', ['id' => $service->getId()]);
        $data = new ServiceToServiceDeviceData();
        $form = $this->createForm(
            ServiceToServiceDeviceType::class,
            $data,
            [
                'action' => $url,
                'client' => $service->getClient(),
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $serviceDevice = $data->serviceDevice) {
            if (! $serviceDevice->getService()->isDeleted()) {
                $form->addError(new FormError('Service device is already attached.'));
            }

            /** @var ConstraintViolationInterface[] $violations */
            $violations = $this->get('validator')->validate($serviceDevice);
            foreach ($violations as $violation) {
                $form->addError(new FormError($violation->getMessage()));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(ServiceDeviceFacade::class)->handleAttachServiceDevice($data->serviceDevice, $service);

            $this->addTranslatedFlash('success', 'Service device has been attached.');

            $this->invalidateTemplateServiceDevice($service);

            return $this->createAjaxResponse();
        }

        return $this->render(
            'client/services/components/edit/service_to_service_device.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}/defer-cancel", name="client_service_defer_cancel", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deferCancelAction(Service $service): Response
    {
        $this->notDeleted($service);

        if (! $service->getSupersededByService()) {
            $this->addTranslatedFlash('error', 'There is no deferred change planned for this service.');
        } else {
            $this->em->getRepository(Service::class)->loadRelatedEntities(
                'invoiceItemsService',
                [$service->getSupersededByService()->getId()]
            );

            if (! $service->getSupersededByService()->getInvoiceItemsService()->isEmpty()) {
                $this->addTranslatedFlash('error', 'Deferred change can\'t be cancelled because it is invoiced.');
            } else {
                $this->get(ServiceFacade::class)->cancelDeferredChange($service);

                $this->addTranslatedFlash('success', 'Deferred change has been cancelled.');
            }
        }

        return $this->redirectToRoute(
            'client_service_show',
            [
                'id' => $service->getId(),
            ]
        );
    }

    /**
     * @Route("/{id}/activate-quoted", name="client_service_activate_quoted", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function activateQuotedAction(Service $service): Response
    {
        $this->notDeleted($service);

        if ($service->getStatus() !== Service::STATUS_QUOTED) {
            $this->addTranslatedFlash('error', 'This service is not quoted.');
        } else {
            $isLeadBefore = $service->getClient()->getIsLead();
            $this->get(ServiceFacade::class)->handleActivateQuoted($service);

            $this->addTranslatedFlash('success', 'Service has been activated.');
            if ($isLeadBefore) {
                $this->addTranslatedFlash(
                    'success',
                    '%clientName% is an active client now!',
                    null,
                    [
                        '%clientName%' => $service->getClient()->getNameForView(),
                    ]
                );
            }
        }

        return $this->redirectToRoute(
            'client_service_show',
            [
                'id' => $service->getId(),
            ]
        );
    }

    /**
     * @Route("/choose-client", name="client_service_choose_client")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function chooseClientAction(Request $request): Response
    {
        $form = $this->createForm(
            ChooseClientType::class,
            null,
            [
                'include_leads' => true,
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Client $client */
            $client = $form->get('client')->getData();

            return $this->createAjaxRedirectResponse(
                'client_service_new',
                [
                    'id' => $client->getId(),
                ]
            );
        }

        return $this->render(
            'client/services/choose_client_modal.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    private function handleNoteForm(Request $request, Service $service, FormInterface $noteForm): ?Response
    {
        $serviceBeforeUpdate = clone $service;
        $noteForm->handleRequest($request);

        if ($noteForm->isSubmitted() && $noteForm->isValid()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);
            $this->get(ServiceFacade::class)->handleUpdate($service, $serviceBeforeUpdate);

            $this->addTranslatedFlash('success', 'Note has been saved.');

            if ($request->isXmlHttpRequest()) {
                $this->invalidateTemplate(
                    'service-overview__note',
                    'client/services/components/view/note.html.twig',
                    [
                        'service' => $service,
                        'client' => $service->getClient(),
                        'noteForm' => $this->createForm(ServiceNoteType::class, $service)->createView(),
                    ]
                );

                return $this->createAjaxResponse();
            }

            return $this->redirectToRoute(
                'client_service_show',
                [
                    'id' => $service->getId(),
                ]
            );
        }

        return null;
    }
}
