<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceSurcharge;
use AppBundle\Facade\ServiceSurchargeFacade;
use AppBundle\Form\ServiceSurchargeType;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/client/service")
 * @PermissionControllerName(ServiceController::class)
 */
class ServiceSurchargeController extends BaseController
{
    use ServiceControllerTrait;

    /**
     * @Route("/surcharge/new/{id}", name="client_service_surcharge_add", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function addServiceSurchargeAction(Request $request, Service $service): Response
    {
        $this->notDeleted($service);
        $this->notDeferred($service);

        $surcharge = new ServiceSurcharge();
        $serviceSurchargeFacade = $this->get(ServiceSurchargeFacade::class);
        $serviceSurchargeFacade->setServiceSurchargeDefaults($service, $surcharge);

        $url = $this->generateUrl('client_service_surcharge_add', ['id' => $service->getId()]);
        $form = $this->createForm(ServiceSurchargeType::class, $surcharge, ['action' => $url]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $serviceSurchargeFacade->handleCreate($surcharge);

            $this->invalidateTemplateServiceInformation($surcharge->getService());
            $this->addTranslatedFlash('success', $this->trans('Surcharge has been created.'));

            return $this->createAjaxResponse();
        }

        return $this->render(
            'client/services/components/edit/surcharge_add.html.twig',
            [
                'form' => $form->createView(),
                'surcharge' => $surcharge,
                'isEdit' => false,
            ]
        );
    }

    /**
     * @Route("/surcharge/{id}/edit", name="service_surcharge_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editServiceSurchargeAction(Request $request, ServiceSurcharge $surcharge): Response
    {
        $service = $surcharge->getService();
        $this->notDeleted($service);
        $this->notDeferred($service);
        $surchargeBeforeUpdate = clone $surcharge;

        $url = $this->generateUrl('service_surcharge_edit', ['id' => $surcharge->getId()]);
        $form = $this->createForm(ServiceSurchargeType::class, $surcharge, ['action' => $url]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(ServiceSurchargeFacade::class)->handleUpdate($surcharge, $surchargeBeforeUpdate);

            $this->invalidateTemplateServiceInformation($service);
            $this->addTranslatedFlash('success', $this->trans('Surcharge has been saved.'));

            return $this->createAjaxResponse();
        }

        return $this->render(
            'client/services/components/edit/surcharge_add.html.twig',
            [
                'form' => $form->createView(),
                'surcharge' => $surcharge,
                'isEdit' => true,
            ]
        );
    }

    /**
     * @Route("/surcharge/{id}/delete", name="service_surcharge_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteServiceSurchargeAction(ServiceSurcharge $surcharge): Response
    {
        $service = $surcharge->getService();
        $this->notDeleted($service);
        $this->notDeferred($service);

        $this->get(ServiceSurchargeFacade::class)->handleDelete($surcharge);
        $this->em->refresh($service);

        $this->invalidateTemplateServiceInformation($service);
        $this->addTranslatedFlash('success', $this->trans('Surcharge has been deleted.'));

        return $this->createAjaxResponse();
    }
}
