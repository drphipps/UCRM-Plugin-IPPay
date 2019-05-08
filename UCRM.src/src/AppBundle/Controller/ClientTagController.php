<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\DataProvider\ClientTagDataProvider;
use AppBundle\Entity\Client;
use AppBundle\Entity\ClientTag;
use AppBundle\Facade\ClientFacade;
use AppBundle\Facade\ClientTagFacade;
use AppBundle\Factory\ClientTagFactory;
use AppBundle\Form\ClientTagType;
use AppBundle\Grid\ClientTag\ClientTagGridFactory;
use AppBundle\Security\Permission;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/other/client-tags")
 */
class ClientTagController extends BaseController
{
    /**
     * @Route("", name="client_tag_index")
     * @Method("GET")
     * @Permission("view")
     * @Searchable(heading="Client tags", path="System -> Other -> Client tags")
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(ClientTagGridFactory::class)->create();
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'client_tag/index.html.twig',
            [
                'grid' => $grid,
            ]
        );
    }

    /**
     * @Route("/new", name="client_tag_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request): Response
    {
        $clientTag = $this->get(ClientTagFactory::class)->create();
        $form = $this->createForm(ClientTagType::class, $clientTag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(ClientTagFacade::class)->handleCreate($clientTag);

            $this->addTranslatedFlash('success', 'Client tag has been created.');

            return $this->redirectToRoute('client_tag_show', ['id' => $clientTag->getId()]);
        }

        return $this->render(
            'client_tag/new.html.twig',
            [
                'clientTag' => $clientTag,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route(
     *     "/new-modal/{client}",
     *     name="client_tag_new_modal",
     *     requirements={
     *         "client": "\d+"
     *     }
     * )
     * @ParamConverter("client", options={"id" = "client"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newModalAction(Request $request, ?Client $client = null): Response
    {
        $clientTag = $this->get(ClientTagFactory::class)->create();
        $url = $this->generateUrl(
            'client_tag_new_modal',
            [
                'client' => $client ? $client->getId() : null,
            ]
        );
        $form = $this->createForm(ClientTagType::class, $clientTag, ['action' => $url]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(ClientTagFacade::class)->handleCreate($clientTag);
            if ($client) {
                $clientBeforeUpdate = clone $client;
                $client->addClientTag($clientTag);
                $this->get(ClientFacade::class)->handleUpdate($client, $clientBeforeUpdate);

                $this->invalidateTemplate(
                    'client-tags',
                    'client/components/view/client_tags.html.twig',
                    [
                        'client' => $client,
                        'clientTags' => $this->get(ClientTagDataProvider::class)->getAllPossibleTagsForClient($client),
                    ]
                );
            }

            $this->addTranslatedFlash('success', 'Client tag has been created.');

            return $this->createAjaxResponse();
        }

        return $this->render(
            'client_tag/new_modal.html.twig',
            [
                'clientTag' => $clientTag,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}", name="client_tag_show", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showAction(ClientTag $clientTag): Response
    {
        return $this->render(
            'client_tag/show.html.twig',
            [
                'clientTag' => $clientTag,
            ]
        );
    }

    /**
     * @Route("/{id}/edit", name="client_tag_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, ClientTag $clientTag): Response
    {
        $form = $this->createForm(ClientTagType::class, $clientTag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(ClientTagFacade::class)->handleUpdate($clientTag);

            $this->addTranslatedFlash('success', 'Client tag has been saved.');

            return $this->redirectToRoute('client_tag_show', ['id' => $clientTag->getId()]);
        }

        return $this->render(
            'client_tag/edit.html.twig',
            [
                'clientTag' => $clientTag,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}/delete", name="client_tag_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(ClientTag $clientTag): Response
    {
        $this->get(ClientTagFacade::class)->handleDelete($clientTag);
        $this->addTranslatedFlash('success', 'Client tag has been deleted.');

        return $this->redirectToRoute('client_tag_index');
    }

    /**
     * @Route(
     *     "/new-select-modal",
     *     name="client_tag_new_select_modal",
     * )
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newSelectModalAction(Request $request): Response
    {
        $clientTag = $this->get(ClientTagFactory::class)->create();
        $form = $this->createForm(
            ClientTagType::class,
            $clientTag,
            [
                'action' => $this->generateUrl('client_tag_new_select_modal'),
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(ClientTagFacade::class)->handleCreate($clientTag);

            $this->addTranslatedFlash('success', 'Client tag has been saved.');

            return $this->createAjaxResponse(
                [
                    'data' => [
                        'value' => $clientTag->getId(),
                        'label' => $clientTag->getName(),
                        'attr' => [
                            'data-color-text' => $clientTag->getColorText(),
                            'data-color-background' => $clientTag->getColorBackground(),
                        ],
                    ],
                ]
            );
        }

        return $this->render(
            'client_tag/new_modal.html.twig',
            [
                'clientTag' => $clientTag,
                'form' => $form->createView(),
            ]
        );
    }
}
