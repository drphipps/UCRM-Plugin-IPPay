<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Controller;

use AppBundle\DataProvider\ServiceDataProvider;
use AppBundle\Entity\Client;
use AppBundle\Entity\Country;
use AppBundle\Entity\Financial\InvoiceItemService;
use AppBundle\Entity\Option;
use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceDevice;
use AppBundle\Entity\ServiceIp;
use AppBundle\Entity\Tariff;
use AppBundle\Entity\Vendor;
use AppBundle\Facade\ServiceFacade;
use AppBundle\Factory\ServiceFactory;
use AppBundle\Form\ServiceType;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Util\Formatter;
use AppBundle\Util\Invoicing;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * @Route("/client/service")
 * @PermissionControllerName(ServiceController::class)
 */
class ServiceFormController extends BaseController
{
    use ServiceControllerTrait;

    private const FORM_MODE_NEW = 1;
    private const FORM_MODE_EDIT = 2;
    private const FORM_MODE_REACTIVATE = 3;
    private const FORM_MODE_DEFER = 4;

    private const FORM_TEMPLATES = [
        self::FORM_MODE_NEW => 'new',
        self::FORM_MODE_EDIT => 'edit',
        self::FORM_MODE_REACTIVATE => 'reactivate',
        self::FORM_MODE_DEFER => 'defer',
    ];

    /**
     * @Route("/new/{id}", name="client_service_new", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request, Client $client): Response
    {
        $this->notDeleted($client);

        return $this->handleNewEditAction(self::FORM_MODE_NEW, $request, $client);
    }

    /**
     * @Route("/new/{id}/{tariff}", name="client_service_new_tariff", requirements={"id": "\d+","tariff": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newWithTariffAction(Request $request, Client $client, ?Tariff $tariff): Response
    {
        $this->notDeleted($client);

        return $this->handleNewEditAction(self::FORM_MODE_NEW, $request, $client, null, $tariff);
    }

    /**
     * @Route("/{id}/reactivate", name="client_service_reactivate", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function reactivateAction(Request $request, Service $service): Response
    {
        $this->notDeleted($service);

        if ($service->getStatus() !== Service::STATUS_ENDED) {
            throw $this->createNotFoundException();
        }

        return $this->handleNewEditAction(self::FORM_MODE_REACTIVATE, $request, $service->getClient(), $service);
    }

    /**
     * @Route("/{id}/edit", name="client_service_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, Service $service): Response
    {
        $this->notDeleted($service);
        $this->notDeferred($service);

        if (! in_array($service->getStatus(), Service::FULL_EDITABLE_STATUSES, true)) {
            throw $this->createNotFoundException();
        }

        $hasInvoices = $this->em->getRepository(InvoiceItemService::class)->hasInvoice($service);

        if ($hasInvoices) {
            return $this->redirectToRoute(
                'client_service_defer',
                [
                    'id' => $service->getId(),
                ]
            );
        }

        return $this->handleNewEditAction(self::FORM_MODE_EDIT, $request, $service->getClient(), $service);
    }

    /**
     * @Route("/{id}/defer", name="client_service_defer", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function deferAction(Request $request, Service $service): Response
    {
        $this->notDeleted($service);

        if (! in_array($service->getStatus(), Service::FULL_EDITABLE_STATUSES, true)) {
            throw $this->createNotFoundException();
        }

        return $this->handleNewEditAction(self::FORM_MODE_DEFER, $request, $service->getClient(), $service);
    }

    /**
     * @Route(
     *     "/{id}/service-surcharge-collection-update",
     *     name="client_service_surcharge_collection_update",
     *     requirements={"id": "\d+"},
     *     options={"expose"=true}
     * )
     * @Method("GET")
     * @Permission("view")
     */
    public function getSurchargeCollectionUpdate(Client $client): JsonResponse
    {
        $form = $this->createForm(
            ServiceType::class,
            $this->get(ServiceFactory::class)->create($client)
        )->createView();

        return new JsonResponse(
            [
                'prototype' => $this->renderView(
                    'client/services/components/edit/surcharge_collection_item_prototype.html.twig',
                    [
                        'form' => $form,
                        'client' => $client,
                    ]
                ),
            ]
        );
    }

    private function handleNewEditAction(
        int $mode,
        Request $request,
        Client $client,
        Service $service = null,
        Tariff $tariff = null
    ): Response {
        $serviceFacade = $this->get(ServiceFacade::class);

        $obsoleteService = null;
        $isEditOfDeferredChange = false;
        if ($mode === self::FORM_MODE_NEW) {
            $service = $this->get(ServiceFactory::class)->create($client);
        } elseif ($mode === self::FORM_MODE_REACTIVATE) {
            $obsoleteService = $service;
            $service = $serviceFacade->createClonedService($service);
        } elseif ($mode === self::FORM_MODE_DEFER) {
            $obsoleteService = $service;

            $isEditOfDeferredChange = (bool) $service->getSupersededByService();
            $service = $service->getSupersededByService() ?? $serviceFacade->createClonedService($service);
        }

        if ($tariff) {
            $service->setTariff($tariff);
        }

        $multipleTaxes = $this->getOption(Option::PRICING_MULTIPLE_TAXES);

        $hasSupersededService = $this->get(ServiceDataProvider::class)->hasSupersededService($service);

        $options = [
            'enableActiveFrom' => in_array($mode, [self::FORM_MODE_NEW, self::FORM_MODE_REACTIVATE], true)
                || (
                    $mode === self::FORM_MODE_EDIT
                    && (
                        in_array(
                            $service->getStatus(),
                            [Service::STATUS_PREPARED, Service::STATUS_PREPARED_BLOCKED, Service::STATUS_QUOTED],
                            true
                        )
                        || (
                            $service->getStatus() === Service::STATUS_ACTIVE
                            && ! $this->em->getRepository(InvoiceItemService::class)->hasInvoice($service)
                        )
                    )
                ),
            'deferredChange' => $mode === self::FORM_MODE_DEFER,
            'multipleTaxes' => $multipleTaxes,
            'enableSetupFee' => ! $hasSupersededService && $service->canSetSetupFee()
                && in_array($mode, [self::FORM_MODE_NEW, self::FORM_MODE_EDIT, self::FORM_MODE_REACTIVATE], true),
            'attr' => [
                'data-signal-check-early-termination-fee' => ServiceController::SIGNAL_CHECK_EARLY_TERMINATION_FEE,
                'data-form-mode' => self::FORM_TEMPLATES[$mode],
                'autocomplete' => 'off',
            ],
            'activation_enable_quoted' => $mode === self::FORM_MODE_NEW,
            'activation_enable_now' => $mode === self::FORM_MODE_NEW,
        ];

        $form = $this->createForm(ServiceType::class, $service, $options);

        if ($form->has('activation')) {
            $form->get('activation')->setData(
                $mode === self::FORM_MODE_NEW
                    ? ServiceType::ACTIVATION_NOW
                    : ServiceType::ACTIVATION_CUSTOM
            );
            if ($options['activation_enable_quoted'] && $client->getIsLead()) {
                $form->get('activation')->setData(ServiceType::ACTIVATION_QUOTED);
            }
        }

        $surcharges = new ArrayCollection();
        foreach ($service->getServiceSurcharges() as $item) {
            $surcharges->add($item);
        }

        $serviceBeforeUpdate = clone $service;
        $form->handleRequest($request);

        if ($response = $this->processTariffChangedSignal($service, $form, $request, false)) {
            return $response;
        }

        if ($response = $this->processCheckEarlyTerminationFeeSignal($service, $form, $request)) {
            return $response;
        }

        if ($form->isSubmitted()) {
            $didAttachServiceDevice = false;
            if ($mode === self::FORM_MODE_NEW) {
                /** @var ServiceDevice $attachServiceDevice */
                $attachServiceDevice = $form->get('serviceDevice')->get('serviceDevice')->getData();

                if ($attachServiceDevice) {
                    foreach ($attachServiceDevice->getServiceIps() as $serviceIp) {
                        $overlappingCount = $this->em
                            ->getRepository(ServiceIp::class)
                            ->getOverlappingRangesCount(
                                $serviceIp->getIpRange()->getFirstIp(),
                                $serviceIp->getIpRange()->getLastIp(),
                                $serviceIp->getId()
                            );

                        if ($overlappingCount) {
                            $form->get('serviceDevice')->get('serviceDevice')->addError(
                                new FormError(
                                    '',
                                    'IP address {{ value }} is already used on a service.',
                                    [
                                        '{{ value }}' => sprintf('"%s"', $serviceIp->getIpRange()->getRangeForView()),
                                    ]
                                )
                            );
                        }
                    }

                    $attachServiceDevice->setService($service);
                    $service->addServiceDevice($attachServiceDevice);
                    $didAttachServiceDevice = true;
                }
            }

            if ($mode === self::FORM_MODE_NEW && ! $didAttachServiceDevice) {
                $deviceInterface = $form->get('deviceInterface')->getNormData();
                $ipRange = $form->get('ipRange')->get('range')->getNormData();
                $macAddress = $form->get('macAddress')->getNormData();
                $vendorId = $form->get('vendor')->getNormData();

                if ($deviceInterface || $ipRange || $macAddress || $vendorId) {
                    if (! $deviceInterface) {
                        $form->get('deviceInterface')->addError(
                            new FormError('You must select a device interface to create service connection.')
                        );
                    }

                    if (! $ipRange) {
                        $form->get('ipRange')->get('range')->addError(
                            new FormError('This value should not be blank.')
                        );
                    }

                    if (! $vendorId) {
                        $form->get('vendor')->addError(
                            new FormError('This value should not be blank.')
                        );
                    }

                    if ($deviceInterface && $ipRange) {
                        $serviceDevice = new ServiceDevice();
                        $serviceDevice->setInterface($deviceInterface);
                        $serviceDevice->setService($service);

                        if (null !== $macAddress) {
                            $serviceDevice->setMacAddress($macAddress);
                        }

                        if (null !== $vendorId) {
                            $vendor = $this->em->find(Vendor::class, $vendorId);
                            $serviceDevice->setVendor($vendor);
                        }

                        $serviceDevice->setCreatePingStatistics($form->get('createPingStatistics')->getNormData());
                        $serviceDevice->setSendPingNotifications($form->get('sendPingNotifications')->getNormData());
                        $serviceDevice->setPingNotificationUser($form->get('pingNotificationUser')->getNormData());
                        $service->addServiceDevice($serviceDevice);

                        $serviceIp = new ServiceIp();
                        if (! count($form->get('ipRange')->getErrors(true))) {
                            $serviceIp->getIpRange()->setRangeFromString($ipRange);
                        }
                        $serviceIp->setServiceDevice($serviceDevice);
                        $serviceDevice->addServiceIp($serviceIp);

                        $this->validateIpForm($form, $serviceIp);
                    }
                }
            }

            if ($form->isValid()) {
                if ($client->isDeleted()) {
                    $this->addTranslatedFlash(
                        'danger',
                        'Client is archived. All actions are prohibited. You can only restore the client.'
                    );

                    return $this->redirectToRoute(
                        'client_show',
                        [
                            'id' => $client->getId(),
                        ]
                    );
                }

                foreach ($service->getServiceSurcharges() as &$item) {
                    $item->setService($service);
                }

                if (isset($serviceDevice)) {
                    $this->em->persist($serviceDevice);
                }

                if (isset($serviceIp)) {
                    $this->em->persist($serviceIp);
                }

                // Reset invoicing after full edit. Applies to all modes.
                $service->setInvoicingLastPeriodEnd(null);

                // We need to remove old surcharges when editing service, or editing deferred change of service.
                // In all other cases there is new service created with only the surcharges needed.
                if ($mode === self::FORM_MODE_EDIT || $isEditOfDeferredChange) {
                    foreach ($surcharges as $item) {
                        if ($service->getServiceSurcharges()->contains($item) === false) {
                            $this->em->remove($item);
                        }
                    }
                }

                if ($mode === self::FORM_MODE_EDIT) {
                    $serviceFacade->handleUpdateWithSetupFee(
                        $service,
                        $serviceBeforeUpdate,
                        $form->get('blockPrepared')->getData(),
                        $options['enableSetupFee'] ? $form->get('setupFeePrice')->getData() : null
                    );
                } elseif ($mode === self::FORM_MODE_REACTIVATE) {
                    $serviceFacade->handleObsolete($service, $obsoleteService, $form->get('blockPrepared')->getData());
                } elseif ($mode === self::FORM_MODE_NEW) {
                    if ($form->get('activation')->getData() === ServiceType::ACTIVATION_QUOTED) {
                        $service->setStatus(Service::STATUS_QUOTED);
                    }

                    $serviceFacade->handleCreateWithSetupFee(
                        $service,
                        $form->get('blockPrepared')->getData(),
                        $options['enableSetupFee'] ? $form->get('setupFeePrice')->getData() : null
                    );
                } elseif ($mode === self::FORM_MODE_DEFER) {
                    $serviceFacade->handleDefer($service, $obsoleteService, $isEditOfDeferredChange);
                } else {
                    throw new \InvalidArgumentException();
                }

                if ($form->has('saveAndQuote')) {
                    /** @var SubmitButton $saveAndQuoteButton */
                    $saveAndQuoteButton = $form->get('saveAndQuote');

                    if ($saveAndQuoteButton->isClicked()) {
                        return $this->redirectToRoute(
                            'client_quote_new',
                            [
                                'id' => $client->getId(),
                                'service_id' => $service->getId(),
                            ]
                        );
                    }
                }

                return $this->redirectToRoute(
                    'client_service_show',
                    [
                        'id' => $service->isDeleted() ? $obsoleteService->getId() : $service->getId(),
                    ]
                );
            }
        }

        if (null !== $service->getTariffPeriod()) {
            list($discountFromChoices, $discountToChoices) = Invoicing::getInvoicedPeriodsForm(
                $service,
                null,
                $this->get(Formatter::class)
            );
            $tariffPeriodId = $service->getTariffPeriod()->getId();
        } else {
            $discountFromChoices = $discountToChoices = [];
            $tariffPeriodId = 0;
        }
        array_unshift($discountFromChoices, $this->trans('Make a choice.'));
        array_unshift($discountToChoices, $this->trans('Make a choice.'));

        $clientHasDeletedServiceDevice = $mode !== self::FORM_MODE_NEW
            ? false
            : $this->em->getRepository(ServiceDevice::class)->hasClientDeleted($client);

        return $this->render(
            'client/services/' . self::FORM_TEMPLATES[$mode] . '.html.twig',
            [
                'client' => $client,
                'form' => $form->createView(),
                'service' => $service,
                'discountFromChoices' => $discountFromChoices,
                'discountToChoices' => $discountToChoices,
                'tariffPeriodId' => $tariffPeriodId,
                'clientHasDeletedServiceDevice' => $clientHasDeletedServiceDevice,
                'obsoletedService' => $obsoleteService,
                'deferredChange' => $mode === self::FORM_MODE_DEFER,
                'fccReportsEnabled' => in_array(
                    $service->getClient()->getOrganization()->getCountry()->getId(),
                    Country::FCC_COUNTRIES,
                    true
                ),
            ]
        );
    }

    private function validateIpForm(Form $form, ServiceIp $serviceIp = null)
    {
        if (! count($form->get('ipRange')->getErrors(true))) {
            if ($serviceIp) {
                // The ipRange field is not mapped, run validations manually.
                $violations = $this->get('validator')->validateProperty($serviceIp, 'ipRange');

                foreach ($violations as $violation) {
                    /** @var ConstraintViolationInterface $violation */
                    $form->get('ipRange')->get('range')->addError(
                        new FormError($violation->getMessage())
                    );
                }

                $warnings = $this->get('validator')->validateProperty($serviceIp, 'ipRange', ['warning']);
            } else {
                $warnings = $this->get('validator')->validate($form->getData(), null, ['warning']);
            }

            foreach ($warnings as $warning) {
                /** @var ConstraintViolationInterface $warning */
                $this->addTranslatedFlash('warning', $warning->getMessage());
            }
        }
    }
}
