<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Entity\Surcharge;
use AppBundle\Facade\SurchargeFacade;
use AppBundle\Form\SurchargeType;
use AppBundle\Grid\Surcharge\SurchargeGridFactory;
use AppBundle\Security\Permission;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/items/surcharges")
 */
class SurchargeController extends BaseController
{
    /**
     * @Route("", name="surcharge_index")
     * @Method("GET")
     * @Permission("view")
     * @Searchable(heading="Surcharges", path="System -> Service plans & Products -> Surcharges")
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(SurchargeGridFactory::class)->create();
        if ($response = $grid->processMultiAction()) {
            return $response;
        }
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'surcharge/index.html.twig',
            [
                'grid' => $grid,
            ]
        );
    }

    /**
     * @Route("/new", name="surcharge_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request): Response
    {
        return $this->handleNewEditAction($request);
    }

    /**
     * @Route("/new-modal", name="surcharge_new_modal")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newModalAction(Request $request): Response
    {
        $surcharge = new Surcharge();

        $url = $this->generateUrl('surcharge_new_modal');
        $form = $this->createForm(SurchargeType::class, $surcharge, ['action' => $url]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($surcharge);
            $this->em->flush();

            $this->addTranslatedFlash('success', 'Surcharge has been created.');

            return $this->createAjaxResponse(
                [
                    'data' => [
                        'value' => $surcharge->getId(),
                        'label' => $surcharge->getName(),
                    ],
                ]
            );
        }

        return $this->render(
            'surcharge/new_modal.html.twig',
            [
                'form' => $form->createView(),
                'surcharge' => $surcharge,
            ]
        );
    }

    /**
     * @Route("/{id}/edit", name="surcharge_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, Surcharge $surcharge): Response
    {
        $this->notDeleted($surcharge);

        return $this->handleNewEditAction($request, $surcharge);
    }

    /**
     * @Route("/{id}/delete", name="surcharge_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(Surcharge $surcharge): Response
    {
        $this->notDeleted($surcharge);

        if (! $this->get(SurchargeFacade::class)->handleDelete($surcharge)) {
            $this->addTranslatedFlash('error', 'Surcharge could not be deleted.');

            return $this->redirectToRoute(
                'surcharge_show',
                [
                    'id' => $surcharge->getId(),
                ]
            );
        }

        $this->addTranslatedFlash('success', 'Surcharge has been deleted.');

        return $this->redirectToRoute('surcharge_index');
    }

    /**
     * @Route("/{id}", name="surcharge_show", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("view")
     */
    public function showAction(Surcharge $surcharge): Response
    {
        $this->notDeleted($surcharge);

        return $this->render(
            'surcharge/show.html.twig',
            [
                'surcharge' => $surcharge,
            ]
        );
    }

    /**
     * @Route("/{id}/json", name="surcharge_json", requirements={"id": "\d+"}, options={"expose": true})
     * @Method("GET")
     * @Permission("view")
     */
    public function getJsonAction(Surcharge $surcharge): Response
    {
        $this->notDeleted($surcharge);

        return new JsonResponse(
            [
                'id' => $surcharge->getId(),
                'name' => $surcharge->getName(),
                'price' => $surcharge->getPrice(),
                'invoiceLabel' => $surcharge->getInvoiceLabel(),
                'taxable' => $surcharge->getTaxable(),
                'taxName' => $surcharge->getTax() ? $surcharge->getTax()->getName() : null,
                'taxRate' => $surcharge->getTax() ? $surcharge->getTax()->getRate() : null,
            ]
        );
    }

    private function handleNewEditAction(Request $request, Surcharge $surcharge = null): Response
    {
        $isEdit = true;
        if (null === $surcharge) {
            $surcharge = new Surcharge();
            $isEdit = false;
        }
        $surchargeBeforeUpdate = clone $surcharge;

        $form = $this->createForm(SurchargeType::class, $surcharge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($isEdit) {
                $this->get(SurchargeFacade::class)->handleUpdate($surcharge, $surchargeBeforeUpdate);
            } else {
                $this->get(SurchargeFacade::class)->handleCreate($surcharge);
            }

            if ($isEdit) {
                $this->addTranslatedFlash('success', 'Surcharge has been saved.');
            } else {
                $this->addTranslatedFlash('success', 'Surcharge has been created.');
            }

            return $this->redirectToRoute(
                'surcharge_show',
                [
                    'id' => $surcharge->getId(),
                    'surcharge' => $surcharge,
                ]
            );
        }

        return $this->render(
            'surcharge/edit.html.twig',
            [
                'form' => $form->createView(),
                'surcharge' => $surcharge,
                'isEdit' => $isEdit,
            ]
        );
    }
}
