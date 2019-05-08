<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Entity\Vendor;
use AppBundle\Form\VendorType;
use AppBundle\Grid\Vendor\VendorGridFactory;
use AppBundle\Security\Permission;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/other/vendors")
 */
class VendorController extends BaseController
{
    /**
     * @Route("", name="vendor_index")
     * @Method("GET")
     * @Permission("view")
     * @Searchable(heading="Vendors", path="System -> Other -> Vendors")
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(VendorGridFactory::class)->create();
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'vendor/index.html.twig',
            [
                'grid' => $grid,
            ]
        );
    }

    /**
     * @Route("/new", name="vendor_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request): Response
    {
        $vendor = new Vendor();
        $form = $this->createForm(VendorType::class, $vendor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($vendor);
            $this->em->flush();

            $this->addTranslatedFlash('success', 'Vendor has been created.');

            return $this->redirectToRoute('vendor_show', ['id' => $vendor->getId()]);
        }

        return $this->render(
            'vendor/new.html.twig',
            [
                'vendor' => $vendor,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}", name="vendor_show", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showAction(Vendor $vendor): Response
    {
        return $this->render(
            'vendor/show.html.twig',
            [
                'vendor' => $vendor,
            ]
        );
    }

    /**
     * @Route("/{id}/edit", name="vendor_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, Vendor $vendor): Response
    {
        $editForm = $this->createForm(VendorType::class, $vendor);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->em->persist($vendor);
            $this->em->flush();

            $this->addTranslatedFlash('success', 'Vendor has been saved.');

            return $this->redirectToRoute('vendor_show', ['id' => $vendor->getId()]);
        }

        return $this->render(
            'vendor/edit.html.twig',
            [
                'vendor' => $vendor,
                'form' => $editForm->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}/delete", name="vendor_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(Vendor $vendor): Response
    {
        if ($vendor->getId() < Vendor::VENDOR_MAX_SYSTEM_ID) {
            $this->addTranslatedFlash('error', 'This vendor cannot be deleted.');

            return $this->redirectToRoute('vendor_index');
        }

        try {
            $this->em->remove($vendor);
            $this->em->flush();

            $this->addTranslatedFlash('success', 'Vendor has been deleted.');
        } catch (ForeignKeyConstraintViolationException  $e) {
            $this->addTranslatedFlash('error', 'Cannot be deleted. Item is used.');
        }

        return $this->redirectToRoute('vendor_index');
    }
}
