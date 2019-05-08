<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Entity\ContactType;
use AppBundle\Facade\ContactTypeFacade;
use AppBundle\Form\ContactTypeType;
use AppBundle\Grid\ContactType\ContactTypeGridFactory;
use AppBundle\Security\Permission;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/other/contact-type")
 */
class ContactTypeController extends BaseController
{
    /**
     * @Route("", name="contact_type_index")
     * @Method("GET")
     * @Permission("view")
     * @Searchable(heading="Contact types", path="System -> Other -> Contact types")
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(ContactTypeGridFactory::class)->create();
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'contact_type/index.html.twig',
            [
                'grid' => $grid,
            ]
        );
    }

    /**
     * @Route("/{id}", name="contact_type_show", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showAction(ContactType $contactType): Response
    {
        return $this->render(
            'contact_type/show.html.twig',
            [
                'contactType' => $contactType,
                'isDeletable' => $contactType->getId() >= ContactType::CONTACT_TYPE_MAX_SYSTEM_ID,
            ]
        );
    }

    /**
     * @Route("/{id}/edit", name="contact_type_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, ContactType $contactType): Response
    {
        $editForm = $this->createForm(ContactTypeType::class, $contactType);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->get(ContactTypeFacade::class)->handleEdit($contactType);

            $this->addTranslatedFlash('success', 'Contact type has been saved.');

            return $this->redirectToRoute('contact_type_show', ['id' => $contactType->getId()]);
        }

        return $this->render(
            'contact_type/edit.html.twig',
            [
                'contactType' => $contactType,
                'form' => $editForm->createView(),
                'isDeletable' => $contactType->getId() >= ContactType::CONTACT_TYPE_MAX_SYSTEM_ID,
            ]
        );
    }

    /**
     * @Route("/{id}/delete", name="contact_type_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(ContactType $contactType): Response
    {
        if ($this->get(ContactTypeFacade::class)->handleDelete($contactType)) {
            $this->addTranslatedFlash('success', 'Contact type has been deleted.');
        } else {
            $this->addTranslatedFlash('error', 'System contact type cannot be deleted.');
        }

        return $this->redirectToRoute('contact_type_index');
    }

    /**
     * @Route("/new", name="contact_type_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request): Response
    {
        $contactType = new ContactType();
        $form = $this->createForm(ContactTypeType::class, $contactType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(ContactTypeFacade::class)->handleNew($contactType);

            $this->addTranslatedFlash('success', 'Contact type has been created.');

            return $this->redirectToRoute('contact_type_show', ['id' => $contactType->getId()]);
        }

        return $this->render(
            'contact_type/new.html.twig',
            [
                'contactType' => $contactType,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/new-modal", name="contact_type_new_modal")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newModalAction(Request $request): Response
    {
        $contactType = new ContactType();

        $url = $this->generateUrl('contact_type_new_modal');
        $form = $this->createForm(ContactTypeType::class, $contactType, ['action' => $url]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(ContactTypeFacade::class)->handleNew($contactType);

            $this->addTranslatedFlash('success', 'Contact type has been created.');

            return $this->createAjaxResponse(
                [
                    'data' => [
                        'value' => $contactType->getId(),
                        'label' => $contactType->getName(),
                    ],
                ]
            );
        }

        return $this->render(
            'contact_type/new_modal.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }
}
