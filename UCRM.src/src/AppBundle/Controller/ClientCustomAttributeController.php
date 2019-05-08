<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Entity\Client;
use AppBundle\Entity\CustomAttribute;
use AppBundle\Facade\CustomAttributeFacade;
use AppBundle\Factory\CustomAttributeFactory;
use AppBundle\Form\ClientCustomAttributeType;
use AppBundle\Form\CustomAttributeType;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/client/custom-attribute")
 * @PermissionControllerName(ClientController::class)
 */
class ClientCustomAttributeController extends BaseController
{
    /**
     * @Route("/new", name="client_custom_attribute_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request): Response
    {
        $this->denyAccessUnlessPermissionGranted(Permission::EDIT, CustomAttributeController::class);
        $attribute = $this->get(CustomAttributeFactory::class)->create(
            CustomAttribute::TYPE_STRING,
            CustomAttribute::ATTRIBUTE_TYPE_CLIENT
        );
        $url = $this->generateUrl('client_custom_attribute_new');
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

            $clientAttributeForm = $this->get('form.factory')->createNamed(
                'client',
                ClientCustomAttributeType::class,
                new Client()
            );

            return new JsonResponse(
                [
                    'html' => $this->renderView(
                        'client_custom_attribute/attribute_input.html.twig',
                        [
                            'form' => $clientAttributeForm->createView(),
                            'newAttribute' => $attribute->getKey(),
                        ]
                    ),
                ]
            );
        }

        return $this->render(
            'client_custom_attribute/new_modal.html.twig',
            [
                'attribute' => $attribute,
                'form' => $form->createView(),
            ]
        );
    }
}
