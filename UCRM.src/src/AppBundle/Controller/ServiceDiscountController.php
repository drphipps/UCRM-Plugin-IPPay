<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Service;
use AppBundle\Facade\ServiceFacade;
use AppBundle\Form\ServiceDiscountType;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Util\Formatter;
use AppBundle\Util\Invoicing;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/client/service")
 * @PermissionControllerName(ServiceController::class)
 */
class ServiceDiscountController extends BaseController
{
    use ServiceControllerTrait;

    /**
     * @Route("/{id}/change-discount", name="client_service_change_discount", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editServiceDiscountAction(Request $request, Service $service): Response
    {
        $this->notDeleted($service);
        $this->notDeferred($service);

        $serviceBeforeUpdate = clone $service;
        $url = $this->generateUrl('client_service_change_discount', ['id' => $service->getId()]);
        $form = $this->createForm(ServiceDiscountType::class, $service, ['action' => $url]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(ServiceFacade::class)->handleUpdate($service, $serviceBeforeUpdate);

            $this->invalidateTemplateServiceInformation($service);
            $this->addTranslatedFlash('success', $this->trans('Discount has been saved.'));

            return $this->createAjaxResponse();
        }

        list($discountFromChoices, $discountToChoices) = Invoicing::getInvoicedPeriodsForm(
            $service,
            null,
            $this->get(Formatter::class)
        );

        array_unshift($discountFromChoices, $this->trans('Make a choice.'));
        array_unshift($discountToChoices, $this->trans('Make a choice.'));

        return $this->render(
            'client/services/components/edit/discount_edit.html.twig',
            [
                'form' => $form->createView(),
                'service' => $service,
                'discountFromChoices' => $discountFromChoices,
                'discountToChoices' => $discountToChoices,
            ]
        );
    }

    /**
     * @Route("/{id}/delete-discount", name="client_service_delete_discount", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteServiceDiscountAction(Service $service): Response
    {
        $this->notDeleted($service);
        $this->notDeferred($service);

        $serviceBeforeUpdate = clone $service;
        $service->setDiscountType(Service::DISCOUNT_NONE);
        $service->setDiscountFrom(null);
        $service->setDiscountTo(null);
        $service->setDiscountValue(null);
        $this->get(ServiceFacade::class)->handleUpdate($service, $serviceBeforeUpdate);

        $this->invalidateTemplateServiceInformation($service);
        $this->addTranslatedFlash('success', $this->trans('Discount has been deleted.'));

        return $this->createAjaxResponse();
    }
}
