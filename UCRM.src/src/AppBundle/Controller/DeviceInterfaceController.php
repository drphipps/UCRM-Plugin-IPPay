<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Device;
use AppBundle\Entity\DeviceInterface;
use AppBundle\Entity\DeviceInterfaceIp;
use AppBundle\Entity\SearchDeviceQueue;
use AppBundle\Facade\DeviceInterfaceFacade;
use AppBundle\Form\DeviceInterfaceIpType;
use AppBundle\Form\DeviceInterfaceType;
use AppBundle\Security\Permission;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/interface")
 */
class DeviceInterfaceController extends BaseController
{
    /**
     * @Route("/new/{id}", name="interface_new", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     *
     * @param Device|null $device
     *
     * @return RedirectResponse|Response
     * @Permission("edit")
     */
    public function newAction(Request $request, Device $device)
    {
        $deviceInterface = new DeviceInterface();
        $deviceInterface->setDevice($device);

        return $this->handleNewEditAction($request, $deviceInterface);
    }

    /**
     * Finds and displays a DeviceInterface entity.
     *
     * @Route("/{id}", name="interface_show", requirements={"id": "\d+"})
     * @Method("GET")
     *
     * @return Response
     * @Permission("view")
     */
    public function showAction(DeviceInterface $deviceInterface)
    {
        $this->notDeleted($deviceInterface);

        $device = $deviceInterface->getDevice();

        return $this->render(
            'device_interface/show.html.twig',
            [
                'deviceInterface' => $deviceInterface,
                'device' => $device,
                'inSyncQueue' => (bool) $this->em->getRepository(SearchDeviceQueue::class)->find($device),
            ]
        );
    }

    /**
     * Displays a form to edit an existing DeviceInterface entity.
     *
     * @Route("/{id}/edit", name="interface_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     *
     * @return RedirectResponse|Response
     * @Permission("edit")
     */
    public function editAction(Request $request, DeviceInterface $deviceInterface)
    {
        $this->notDeleted($deviceInterface);

        return $this->handleNewEditAction($request, $deviceInterface);
    }

    /**
     * Finds and displays a DeviceInterface entity.
     *
     * @Route("/{id}/ip/new", name="device_interface_ip_add", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     *
     * @return Response
     * @Permission("edit")
     */
    public function addInterfaceIpAction(Request $request, DeviceInterface $deviceInterface)
    {
        $this->notDeleted($deviceInterface);

        $deviceInterfaceIp = new DeviceInterfaceIp();
        $deviceInterface->addInterfaceIp($deviceInterfaceIp);

        $url = $this->generateUrl('device_interface_ip_add', ['id' => $deviceInterface->getId()]);
        $form = $this->createForm(DeviceInterfaceIpType::class, $deviceInterfaceIp, ['action' => $url]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(DeviceInterfaceFacade::class)->handleCreateIp($deviceInterfaceIp);

            $this->invalidateTemplate(
                'interface-ips',
                'device_interface/components/ip_addresses.html.twig',
                [
                    'deviceInterface' => $deviceInterface,
                ]
            );

            $this->addTranslatedFlash('success', $this->trans('IP address has been created.'));

            return $this->createAjaxResponse();
        }

        return $this->render(
            'device_interface/device_interface_ip_add.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/ip/{id}/delete", name="interface_ip_remove", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     *
     * @return JsonResponse
     * @Permission("edit")
     */
    public function deleteInterfaceIpAction(DeviceInterfaceIp $deviceInterfaceIp)
    {
        $this->get(DeviceInterfaceFacade::class)->handleDeleteIp($deviceInterfaceIp);

        $this->invalidateTemplate(
            'interface-ips',
            'device_interface/components/ip_addresses.html.twig',
            [
                'deviceInterface' => $deviceInterfaceIp->getInterface(),
            ]
        );

        $this->addTranslatedFlash('success', $this->trans('IP address has been deleted.'));

        return $this->createAjaxResponse();
    }

    /**
     * @Route("/{id}/delete", name="interface_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     *
     * @return RedirectResponse
     * @Permission("edit")
     */
    public function deleteAction(DeviceInterface $deviceInterface)
    {
        $this->notDeleted($deviceInterface);

        try {
            $this->get(DeviceInterfaceFacade::class)->handleArchive($deviceInterface);

            $this->addTranslatedFlash('success', 'Interface has been deleted.');
        } catch (ForeignKeyConstraintViolationException $e) {
            $this->addTranslatedFlash('error', 'Cannot be deleted. Item is used.');
        }

        return $this->redirectToRoute('device_show_interfaces', ['id' => $deviceInterface->getDevice()->getId()]);
    }

    /**
     * @Route("/{id}/get-ips-on-device", name="get_ips_on_device", options={"expose"=true}, requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("edit")
     *
     * @return JsonResponse
     * @Permission("view")
     */
    public function getIpsOnDevice(DeviceInterface $deviceInterface)
    {
        $this->notDeleted($deviceInterface);

        return new JsonResponse(
            [
                'data' => implode(
                    ' ',
                    [
                        $this->trans('IP addresses on device interface') . ':',
                        $deviceInterface->getIpsHumanize(),
                    ]
                ),
            ]
        );
    }

    /**
     * @return RedirectResponse|Response
     */
    private function handleNewEditAction(Request $request, DeviceInterface $deviceInterface)
    {
        $isEdit = $deviceInterface->getId() !== null;
        $deviceInterfaceBeforeUpdate = clone $deviceInterface;

        $form = $this->createForm(DeviceInterfaceType::class, $deviceInterface);
        foreach ($deviceInterface->getInterfaceIps() as $key => $ip) {
            $form->get('interfaceIps')->get($key)->get('ipRange')->setData(
                $ip->getIpRange()
            );
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $deviceInterfaceFacade = $this->get(DeviceInterfaceFacade::class);
            if ($isEdit) {
                $deviceInterfaceFacade->handleUpdate($deviceInterface, $deviceInterfaceBeforeUpdate);

                $this->addTranslatedFlash('success', 'Interface has been saved.');
            } else {
                $deviceInterfaceFacade->handleCreate($deviceInterface);

                $this->addTranslatedFlash('success', 'Interface has been created.');
            }

            return $this->redirectToRoute('interface_show', ['id' => $deviceInterface->getId()]);
        }

        return $this->render(
            $isEdit ? 'device_interface/edit.html.twig' : 'device_interface/new.html.twig',
            [
                'deviceInterface' => $deviceInterface,
                'form' => $form->createView(),
            ]
        );
    }
}
