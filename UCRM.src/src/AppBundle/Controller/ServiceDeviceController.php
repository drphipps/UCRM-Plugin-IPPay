<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Entity\Option;
use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceDevice;
use AppBundle\Entity\Vendor;
use AppBundle\Facade\ServiceDeviceFacade;
use AppBundle\Form\Data\ServiceDeviceToServiceData;
use AppBundle\Form\ServiceDeviceToServiceType;
use AppBundle\Form\ServiceDeviceType;
use AppBundle\Grid\ServiceDevice\ServiceDeviceLogGridFactory;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/client/service")
 * @PermissionControllerName(ServiceController::class)
 */
class ServiceDeviceController extends BaseController
{
    use ServiceControllerTrait;

    /**
     * @Route("/{id}/device/new", name="client_service_device_add", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function addAction(Request $request, Service $service): Response
    {
        $this->notDeleted($service);

        $serviceDevice = new ServiceDevice();
        $serviceDeviceFacade = $this->get(ServiceDeviceFacade::class);
        $serviceDeviceFacade->setDefaults($service, $serviceDevice);

        $url = $this->generateUrl('client_service_device_add', ['id' => $service->getId()]);

        return $this->handleNewEditAction(
            $request,
            $service,
            $serviceDevice,
            $url
        );
    }

    /**
     * @Route("/{id}/sync-devices", name="client_service_sync_devices", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function syncAction(Service $service): Response
    {
        list($synced, $alreadySynced) = $this->get(ServiceDeviceFacade::class)
            ->handleAddToSyncQueueMultiple($service->getServiceDevices()->toArray());

        if ($synced > 0) {
            $this->addTranslatedFlash(
                'success',
                'Added %count% devices to sync queue.',
                $synced,
                [
                    '%count%' => $synced,
                ]
            );
        }

        if ($alreadySynced > 0) {
            $this->addTranslatedFlash(
                'info',
                '%count% devices already in sync queue.',
                $alreadySynced,
                [
                    '%count%' => $alreadySynced,
                ]
            );
        }

        return $this->redirectToRoute(
            'client_service_show',
            [
                'id' => $service->getId(),
            ]
        );
    }

    /**
     * @Route("/device/{id}/edit", name="client_service_device_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, ServiceDevice $serviceDevice): Response
    {
        $service = $serviceDevice->getService();

        $url = $this->generateUrl('client_service_device_edit', ['id' => $serviceDevice->getId()]);

        return $this->handleNewEditAction(
            $request,
            $service,
            $serviceDevice,
            $url
        );
    }

    /**
     * @Route("/device/{id}/attach", name="client_service_device_attach", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function attachToServiceAction(Request $request, ServiceDevice $serviceDevice): Response
    {
        $url = $this->generateUrl('client_service_device_attach', ['id' => $serviceDevice->getId()]);
        $data = new ServiceDeviceToServiceData();
        $form = $this->createForm(
            ServiceDeviceToServiceType::class,
            $data,
            [
                'action' => $url,
                'client' => $serviceDevice->getService()->getClient(),
            ]
        );
        $alreadyAttached = ! $serviceDevice->getService()->isDeleted();
        $form->handleRequest($request);

        if ($alreadyAttached) {
            $form->addError(new FormError('Service device is already attached.'));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(ServiceDeviceFacade::class)->handleAttachServiceDevice($serviceDevice, $data->service);

            $this->addTranslatedFlash('success', 'Service device has been attached.');

            return $this->createAjaxRedirectResponse(
                'client_service_show',
                [
                    'id' => $serviceDevice->getService()->getId(),
                ]
            );
        }

        return $this->render(
            'client/services/components/edit/service_device_to_service.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/device/{id}/delete", name="client_service_device_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(ServiceDevice $serviceDevice): Response
    {
        $service = $serviceDevice->getService();

        $this->get(ServiceDeviceFacade::class)->handleDelete($serviceDevice);

        $this->addTranslatedFlash('success', $this->trans('Service device has been deleted.'));
        $this->invalidateTemplateServiceDevice($service);

        return $this->createAjaxResponse();
    }

    /**
     * @Route("/{id}/device-logs", name="client_service_device_show_logs", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function logsAction(Request $request, Service $service): Response
    {
        $gridFactory = $this->get(ServiceDeviceLogGridFactory::class);

        $grids = [];

        foreach ($service->getServiceDevices() as $device) {
            $grid = $gridFactory->create($device);
            $grids[$device->getId()] = $grid;

            if ($parameters = $grid->processAjaxRequest($request)) {
                return $this->createAjaxResponse($parameters);
            }
        }

        return $this->render(
            'client/services/components/view/device_logs_modal.html.twig',
            [
                'service' => $service,
                'grids' => $grids,
            ]
        );
    }

    private function handleNewEditAction(
        Request $request,
        Service $service,
        ServiceDevice $serviceDevice,
        string $url
    ): Response {
        $serviceDeviceFacade = $this->get(ServiceDeviceFacade::class);
        $isEdit = null !== $serviceDevice->getId();
        $oldServiceDevice = clone $serviceDevice;

        $options = [
            'action' => $url,
            'vendors' => $this->em->getRepository(Vendor::class)->getVendors(),
            'vendorId' => $serviceDevice->getVendor() ? $serviceDevice->getVendor()->getId() : 0,
        ];

        $form = $this->createForm(ServiceDeviceType::class, $serviceDevice, $options);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $httpData = $request->request->get('service_device');
            $macAddress = $form->getNormData()->getMacAddress();
            $macAddressExists = $this->em->getRepository(ServiceDevice::class)->findOneBy(
                [
                    'macAddress' => $macAddress,
                ]
            );

            if ($macAddress && $macAddressExists) {
                $this->addTranslatedFlash('warning', 'MAC address exists.');
            }

            if (isset($httpData['loginPassword'])) {
                $serviceDevice->setLoginPassword($httpData['loginPassword']);
            }

            $vendorId = $form->get('vendorId')->getNormData();
            $vendor = $this->em->find(Vendor::class, $vendorId);

            $serviceDevice->setVendor($vendor);
            $serviceDevice->setMacAddress($macAddress);

            if ($isEdit) {
                $serviceDeviceFacade->handleUpdate($serviceDevice, $oldServiceDevice);
                $this->addTranslatedFlash('success', $this->trans('Service device has been edited.'));
            } else {
                $serviceDeviceFacade->handleCreate($serviceDevice);
                $this->addTranslatedFlash('success', $this->trans('Service device has been created.'));
            }

            $this->invalidateTemplateServiceDevice($service);

            return $this->createAjaxResponse();
        }

        $qosDestinationGateway = $this->getOption(Option::QOS_ENABLED)
            && $this->getOption(Option::QOS_DESTINATION) === Option::QOS_DESTINATION_GATEWAY;

        return $this->render(
            'client/services/components/edit/service_device_modal.html.twig',
            [
                'form' => $form->createView(),
                'isEdit' => $isEdit,
                'device' => $serviceDevice,
                'qosDestinationGateway' => $qosDestinationGateway,
            ]
        );
    }
}
