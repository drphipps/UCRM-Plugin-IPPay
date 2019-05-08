<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Entity\CustomAttribute;
use AppBundle\Facade\CustomAttributeFacade;
use AppBundle\Factory\CustomAttributeFactory;
use AppBundle\Form\CustomAttributeType;
use AppBundle\Grid\CustomAttribute\CustomAttributeGridFactory;
use AppBundle\Security\Permission;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/other/custom-attributes")
 */
class CustomAttributeController extends BaseController
{
    /**
     * @Route("", name="custom_attribute_index")
     * @Method("GET")
     * @Permission("view")
     * @Searchable(heading="Custom attributes", path="System -> Other -> Custom attributes")
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(CustomAttributeGridFactory::class)->create();
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'custom_attribute/index.html.twig',
            [
                'grid' => $grid,
            ]
        );
    }

    /**
     * @Route("/new", name="custom_attribute_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request): Response
    {
        $attribute = $this->get(CustomAttributeFactory::class)->create(CustomAttribute::TYPE_STRING, null);
        $form = $this->createForm(CustomAttributeType::class, $attribute);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(CustomAttributeFacade::class)->handleNew($attribute);

            $this->addTranslatedFlash('success', 'Custom attribute has been created.');

            return $this->redirectToRoute('custom_attribute_show', ['id' => $attribute->getId()]);
        }

        return $this->render(
            'custom_attribute/new.html.twig',
            [
                'attribute' => $attribute,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}", name="custom_attribute_show", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showAction(CustomAttribute $attribute): Response
    {
        return $this->render(
            'custom_attribute/show.html.twig',
            [
                'attribute' => $attribute,
            ]
        );
    }

    /**
     * @Route("/{id}/edit", name="custom_attribute_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, CustomAttribute $attribute): Response
    {
        $editForm = $this->createForm(
            CustomAttributeType::class,
            $attribute,
            [
                'include_attribute_type' => false,
            ]
        );
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->get(CustomAttributeFacade::class)->handleEdit($attribute);

            $this->addTranslatedFlash('success', 'Custom attribute has been saved.');

            return $this->redirectToRoute('custom_attribute_show', ['id' => $attribute->getId()]);
        }

        return $this->render(
            'custom_attribute/edit.html.twig',
            [
                'attribute' => $attribute,
                'form' => $editForm->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}/delete", name="custom_attribute_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(CustomAttribute $attribute): Response
    {
        try {
            $this->get(CustomAttributeFacade::class)->handleDelete($attribute);

            $this->addTranslatedFlash('success', 'Custom attribute has been deleted.');
        } catch (ForeignKeyConstraintViolationException  $e) {
            $this->addTranslatedFlash('error', 'Cannot be deleted. Item is used.');
        }

        return $this->redirectToRoute('custom_attribute_index');
    }
}
