<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Entity\ClientZonePage;
use AppBundle\Facade\ClientZonePageFacade;
use AppBundle\Form\ClientZonePageType;
use AppBundle\Grid\Settings\ClientZonePageGridFactory;
use AppBundle\Security\Permission;
use AppBundle\Util\Helpers;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/customization/client-zone-pages")
 */
class ClientZonePageController extends BaseController
{
    /**
     * @Route("", name="client_zone_page_index")
     * @Method("GET")
     * @Permission("view")
     * @Searchable(heading="Client zone pages", path="System -> Customization -> Client zone pages")
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(ClientZonePageGridFactory::class)->create();
        if ($response = $grid->processMultiAction()) {
            return $response;
        }
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'setting/client_zone/page/index.html.twig',
            [
                'grid' => $grid,
            ]
        );
    }

    /**
     * @Route("/{id}", name="client_zone_page_show", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showAction(ClientZonePage $clientZonePage)
    {
        return $this->render(
            'setting/client_zone/page/show.html.twig',
            [
                'clientZonePage' => $clientZonePage,
            ]
        );
    }

    /**
     * @Route("/new", name="client_zone_page_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request): Response
    {
        return $this->handleNewEditAction($request);
    }

    /**
     * @Route("/{id}/edit", name="client_zone_page_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, ClientZonePage $clientZonePage): Response
    {
        return $this->handleNewEditAction($request, $clientZonePage);
    }

    /**
     * @Route("/{id}/delete", name="client_zone_page_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(ClientZonePage $clientZonePage)
    {
        $this->get(ClientZonePageFacade::class)->handleDelete($clientZonePage);

        $this->addTranslatedFlash('success', 'Item has been removed.');

        return $this->redirectToRoute('client_zone_page_index');
    }

    /**
     * @Route("/{id}/position-up", name="client_zone_page_position_up", requirements={"id": "\d+"})
     * @Permission("edit")
     * @CsrfToken()
     */
    public function positionUpAction(ClientZonePage $clientZonePage)
    {
        $this->get(ClientZonePageFacade::class)->handlePositionUp($clientZonePage);

        $this->addTranslatedFlash('success', 'Item has been moved.');

        return $this->redirectToRoute('client_zone_page_index');
    }

    /**
     * @Route("/{id}/position-down", name="client_zone_page_position_down", requirements={"id": "\d+"})
     * @Permission("edit")
     * @CsrfToken()
     */
    public function positionDownAction(ClientZonePage $clientZonePage)
    {
        $this->get(ClientZonePageFacade::class)->handlePositionDown($clientZonePage);

        $this->addTranslatedFlash('success', 'Item has been moved.');

        return $this->redirectToRoute('client_zone_page_index');
    }

    private function handleNewEditAction(Request $request, ?ClientZonePage $clientZonePage = null): Response
    {
        $clientZonePage = $clientZonePage ?? new ClientZonePage();
        $isEdit = (bool) $clientZonePage->getId();

        $form = $this->createForm(ClientZonePageType::class, $clientZonePage);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (Helpers::isDemo()) {
                $this->addTranslatedFlash('error', 'This feature is not available in the demo.');

                return $this->redirectToRoute('client_zone_page_index');
            }

            if ($isEdit) {
                $this->get(ClientZonePageFacade::class)->handleUpdate($clientZonePage);

                $this->addTranslatedFlash('success', 'Item has been edited.');
            } else {
                $this->get(ClientZonePageFacade::class)->handleCreate($clientZonePage);

                $this->addTranslatedFlash('success', 'Item has been created.');
            }

            return $this->redirectToRoute(
                'client_zone_page_show',
                [
                    'id' => $clientZonePage->getId(),
                ]
            );
        }

        return $this->render(
            'setting/client_zone/page/edit.html.twig',
            [
                'form' => $form->createView(),
                'isEdit' => $isEdit,
                'clientZonePage' => $clientZonePage,
            ]
        );
    }
}
