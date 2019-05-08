<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Entity\Tax;
use AppBundle\Facade\TaxFacade;
use AppBundle\Form\TaxType;
use AppBundle\Grid\Tax\TaxGridFactory;
use AppBundle\Security\Permission;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/billing/taxes")
 */
class TaxController extends BaseController
{
    const TAX_MAX_DEFAULT = 3;

    /**
     * @Route("", name="tax_index")
     * @Method("GET")
     * @Permission("view")
     * @Searchable(heading="Taxes", path="System -> Billing -> Taxes")
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(TaxGridFactory::class)->create();
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'tax/index.html.twig',
            [
                'grid' => $grid,
            ]
        );
    }

    /**
     * @Route("/new", name="tax_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request): Response
    {
        $tax = new Tax();
        $form = $this->createForm(TaxType::class, $tax);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(TaxFacade::class)->handleCreate($tax);

            $this->addTranslatedFlash('success', 'Tax has been created.');

            return $this->redirectToRoute('tax_show', ['id' => $tax->getId()]);
        }

        return $this->render(
            'tax/new.html.twig',
            [
                'tax' => $tax,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/new-modal", name="tax_new_modal")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newModalAction(Request $request): Response
    {
        $tax = new Tax();
        if (! $tax->getName() && $newName = $request->get('name')) {
            $tax->setName($newName);
        }

        $url = $this->generateUrl('tax_new_modal');
        $form = $this->createForm(TaxType::class, $tax, ['action' => $url]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(TaxFacade::class)->handleCreate($tax);

            $this->addTranslatedFlash('success', 'Tax has been created.');

            return $this->createAjaxResponse(
                [
                    'data' => [
                        'value' => $tax->getId(),
                        'label' => $tax->getName(),
                    ],
                ]
            );
        }

        return $this->render(
            'tax/new_modal.html.twig',
            [
                'tax' => $tax,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}", name="tax_show", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showAction(Tax $tax): Response
    {
        $this->notDeleted($tax);

        return $this->render(
            'tax/show.html.twig',
            [
                'tax' => $tax,
            ]
        );
    }

    /**
     * @Route("/{id}/edit", name="tax_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, Tax $tax): Response
    {
        $this->notDeleted($tax);

        $form = $this->createForm(TaxType::class, $tax);

        // rate field is disabled in edit form - we must restore the rate after handleRequest method
        $rate = $tax->getRate();
        $form->handleRequest($request);
        $tax->setRate($rate);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(TaxFacade::class)->handleUpdate($tax);

            $this->addTranslatedFlash('success', 'Tax has been saved.');

            return $this->redirectToRoute('tax_show', ['id' => $tax->getId()]);
        }

        return $this->render(
            'tax/edit.html.twig',
            [
                'tax' => $tax,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}/replace", name="tax_replace", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function replaceAction(Request $request, Tax $oldTax): Response
    {
        $this->notDeleted($oldTax);

        $tax = new Tax();
        $form = $this->createForm(TaxType::class, $tax);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(TaxFacade::class)->handleReplaceTax($tax, $oldTax);

            $this->addTranslatedFlash('success', 'Tax has been replaced.');

            return $this->redirectToRoute('tax_show', ['id' => $tax->getId()]);
        }

        return $this->render(
            'tax/replace.html.twig',
            [
                'oldTax' => $oldTax,
                'tax' => $tax,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}/default", name="tax_default", requirements={"id": "\d+"}, options={"expose": true})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     * @CsrfToken(methods={"GET", "POST"})
     */
    public function defaultAction(Tax $tax): Response
    {
        $this->notDeleted($tax);

        $taxRepository = $this->em->getRepository(Tax::class);
        $count = $taxRepository->getCountOfSelected();

        if ($count >= self::TAX_MAX_DEFAULT && $tax->getSelected() !== true) {
            $this->addTranslatedFlash(
                'warning',
                'It\'s not possible to mark more than %s taxes as default.',
                null,
                [
                    '%s' => self::TAX_MAX_DEFAULT,
                ]
            );
            $newState = $tax->getSelected();
        } else {
            $newState = ! $tax->getSelected();
            $this->get(TaxFacade::class)->handleSetDefault($tax, $newState);

            if ($newState) {
                $this->addTranslatedFlash('success', 'Tax has been set as default.');
            } else {
                $this->addTranslatedFlash('success', 'Tax has been set as not default.');
            }
        }

        return $this->createAjaxResponse(['isDefault' => $newState]);
    }

    /**
     * @Route("/{id}/delete", name="tax_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(Tax $tax): Response
    {
        $this->notDeleted($tax);

        try {
            $this->get(TaxFacade::class)->handleDelete($tax);

            $this->addTranslatedFlash('success', 'Tax has been deleted.');
        } catch (ForeignKeyConstraintViolationException  $e) {
            $this->addTranslatedFlash('error', 'Cannot be deleted. Item is used.');
        }

        return $this->redirectToRoute('tax_index');
    }
}
