<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Service;
use AppBundle\Entity\Tax;
use AppBundle\Facade\ServiceFacade;
use AppBundle\Form\ServiceTaxType;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/client/service")
 * @PermissionControllerName(ServiceController::class)
 */
class ServiceTaxController extends BaseController
{
    use ServiceControllerTrait;

    /**
     * @Route("/tax/new/{id}", name="client_service_tax_add", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function addServiceTaxAction(Request $request, Service $service): Response
    {
        $this->notDeleted($service);
        $this->notDeferred($service);
        $this->notServicePlanTax($service);
        $serviceBeforeUpdate = clone $service;

        $url = $this->generateUrl('client_service_tax_add', ['id' => $service->getId()]);
        $form = $this->createForm(
            ServiceTaxType::class,
            $service,
            [
                'action' => $url,
                'removeTaxes' => array_filter(
                    [
                        $service->getTax1(),
                        $service->getTax2(),
                        $service->getTax3(),
                    ]
                ),
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $taxField = $form->get('tax');
            $tax = $taxField->getData();
            if (! $tax) {
                $taxField->addError(new FormError('This field is required.'));
            }

            $violations = [];
            if (! $service->getTax1()) {
                $service->setTax1($tax);
                $violations = $this->get('validator')->validateProperty($service, 'tax1');
            } elseif (! $service->getTax2()) {
                $service->setTax2($tax);
                $violations = $this->get('validator')->validateProperty($service, 'tax2');
            } elseif (! $service->getTax3()) {
                $service->setTax3($tax);
                $violations = $this->get('validator')->validateProperty($service, 'tax3');
            } else {
                $taxField->addError(new FormError('You cannot add more than 3 taxes.'));
            }

            foreach ($violations as $violation) {
                $taxField->addError(
                    new FormError($violation->getMessage())
                );
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(ServiceFacade::class)->handleUpdate($service, $serviceBeforeUpdate);

            $this->invalidateTemplateServiceInformation($service);
            $this->addTranslatedFlash('success', 'Tax has been added.');

            return $this->createAjaxResponse();
        }

        return $this->render(
            'client/services/components/edit/tax_add.html.twig',
            [
                'form' => $form->createView(),
                'service' => $service,
            ]
        );
    }

    /**
     * @Route(
     *     "/{serviceId}/tax/{id}/delete",
     *     name="client_service_tax_delete",
     *     requirements={"serviceId": "\d+", "id": "\d+"}
     * )
     * @Method("GET")
     * @CsrfToken()
     *
     * @ParamConverter("service", options={"id" = "serviceId"})
     * @Permission("edit")
     */
    public function deleteServiceTaxAction(Service $service, Tax $tax): Response
    {
        $this->notDeleted($service);
        $this->notDeferred($service);
        $this->notServicePlanTax($service);
        $serviceBeforeUpdate = clone $service;

        if ($service->getTax1() === $tax) {
            $service->setTax1(null);
        } elseif ($service->getTax2() === $tax) {
            $service->setTax2(null);
        } elseif ($service->getTax3() === $tax) {
            $service->setTax3(null);
        }

        $this->get(ServiceFacade::class)->handleUpdate($service, $serviceBeforeUpdate);

        $this->invalidateTemplateServiceInformation($service);
        $this->addTranslatedFlash('success', 'Tax has been deleted.');

        return $this->createAjaxResponse();
    }

    /**
     * @Route(
     *     "/{serviceId}/tax/{oldTaxId}/change/{newTaxId}",
     *     name="client_service_tax_change",
     *     requirements={"serviceId": "\d+", "oldTaxId": "\d+", "newTaxId": "\d+"}
     * )
     * @Method("GET")
     * @CsrfToken()
     *
     * @ParamConverter("service", options={"id" = "serviceId"})
     * @ParamConverter("oldTax", options={"id" = "oldTaxId"})
     * @ParamConverter("newTax", options={"id" = "newTaxId"})
     * @Permission("edit")
     */
    public function changeServiceTaxAction(Service $service, Tax $oldTax, Tax $newTax): Response
    {
        $this->notDeleted($service);
        $this->notDeferred($service);
        $this->notServicePlanTax($service);
        $serviceBeforeUpdate = clone $service;

        $violations = [];
        if ($service->getTax1() === $oldTax) {
            $service->setTax1($newTax);
            $violations = $this->get('validator')->validateProperty($service, 'tax1');
        } elseif ($service->getTax2() === $oldTax) {
            $service->setTax2($newTax);
            $violations = $this->get('validator')->validateProperty($service, 'tax2');
        } elseif ($service->getTax3() === $oldTax) {
            $service->setTax3($newTax);
            $violations = $this->get('validator')->validateProperty($service, 'tax3');
        }

        foreach ($violations as $violation) {
            $this->addTranslatedFlash('error', $violation->getMessage());

            return $this->createAjaxResponse();
        }

        $this->get(ServiceFacade::class)->handleUpdate($service, $serviceBeforeUpdate);

        $this->invalidateTemplateServiceInformation($service);
        $this->addTranslatedFlash('success', 'Tax has been changed.');

        return $this->createAjaxResponse();
    }

    private function notServicePlanTax(Service $service): void
    {
        if ($service->getTariff()->getTaxable() && $service->getTariff()->getTax()) {
            throw $this->createNotFoundException();
        }
    }
}
