<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Entity\CustomAttribute;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Facade\CustomAttributeFacade;
use AppBundle\Factory\CustomAttributeFactory;
use AppBundle\Form\CustomAttributeType;
use AppBundle\Form\InvoiceCustomAttributeType;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/client/invoice/custom-attribute")
 * @PermissionControllerName(InvoiceController::class)
 */
class InvoiceCustomAttributeController extends BaseController
{
    /**
     * @Route("/new", name="invoice_custom_attribute_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request): Response
    {
        $this->denyAccessUnlessPermissionGranted(Permission::EDIT, CustomAttributeController::class);
        $attribute = $this->get(CustomAttributeFactory::class)->create(
            CustomAttribute::TYPE_STRING,
            CustomAttribute::ATTRIBUTE_TYPE_INVOICE
        );
        $url = $this->generateUrl('invoice_custom_attribute_new');
        $form = $this->createForm(
            CustomAttributeType::class,
            $attribute,
            [
                'action' => $url,
                'include_attribute_type' => false,
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(CustomAttributeFacade::class)->handleNew($attribute);

            $invoiceAttributeForm = $this->get('form.factory')->createNamed(
                'invoice',
                InvoiceCustomAttributeType::class,
                new Invoice()
            );

            return new JsonResponse(
                [
                    'html' => $this->renderView(
                        'invoice_custom_attribute/attribute_input.html.twig',
                        [
                            'form' => $invoiceAttributeForm->createView(),
                            'newAttribute' => $attribute->getKey(),
                        ]
                    ),
                ]
            );
        }

        return $this->render(
            'invoice_custom_attribute/new_modal.html.twig',
            [
                'attribute' => $attribute,
                'form' => $form->createView(),
            ]
        );
    }
}
