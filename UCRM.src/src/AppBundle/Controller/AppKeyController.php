<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\DataProvider\AppKeyDataProvider;
use AppBundle\Entity\AppKey;
use AppBundle\Facade\AppKeyFacade;
use AppBundle\Form\AppKeyType;
use AppBundle\Grid\AppKey\AppKeyGridFactory;
use AppBundle\Security\Permission;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/security/app-keys")
 */
class AppKeyController extends BaseController
{
    /**
     * @Route("", name="app_key_index")
     * @Method("GET")
     * @Permission("view")
     * @Searchable(heading="App keys", path="System -> Users -> App keys")
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(AppKeyGridFactory::class)->create();
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'app_key/index.html.twig',
            [
                'grid' => $grid,
            ]
        );
    }

    /**
     * @Route("/{id}", name="app_key_show", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showAction(AppKey $appKey): Response
    {
        $this->notDeleted($appKey);

        if ($appKey->getPlugin()) {
            throw $this->createNotFoundException();
        }

        return $this->render(
            'app_key/show.html.twig',
            [
                'appKey' => $appKey,
            ]
        );
    }

    /**
     * @Route("/new", name="app_key_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request): Response
    {
        return $this->handleNewEditAction($request);
    }

    /**
     * @Route("/{id}/edit", name="app_key_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, AppKey $appKey): Response
    {
        $this->notDeleted($appKey);

        if ($appKey->getPlugin()) {
            throw $this->createNotFoundException();
        }

        return $this->handleNewEditAction($request, $appKey);
    }

    /**
     * @Route("/{id}/delete", name="app_key_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(AppKey $appKey): Response
    {
        $this->notDeleted($appKey);

        if ($appKey->getPlugin()) {
            throw $this->createNotFoundException();
        }

        $this->get(AppKeyFacade::class)->handleDelete($appKey);
        $this->addTranslatedFlash('success', 'App key has been deleted.');

        return $this->redirectToRoute('app_key_index');
    }

    private function handleNewEditAction(Request $request, AppKey $appKey = null): Response
    {
        $isEdit = (bool) $appKey;

        if (! $isEdit) {
            $appKey = new AppKey();
            $appKey->setType(AppKey::TYPE_READ);
            $appKey->setCreatedDate(new \DateTime());
            $appKey->setKey($this->get(AppKeyDataProvider::class)->getUniqueKey());
        }

        $form = $this->createForm(AppKeyType::class, $appKey);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $appKeyFacade = $this->get(AppKeyFacade::class);
            if ($isEdit) {
                $appKeyFacade->handleEdit($appKey);
                $this->addTranslatedFlash('success', 'App key has been saved.');
            } else {
                $appKeyFacade->handleNew($appKey);
                $this->addTranslatedFlash('success', 'App key has been added.');
            }

            return $this->redirectToRoute(
                'app_key_show',
                [
                    'id' => $appKey->getId(),
                ]
            );
        }

        return $this->render(
            'app_key/edit.html.twig',
            [
                'form' => $form->createView(),
                'isEdit' => $isEdit,
                'appKey' => $appKey,
            ]
        );
    }
}
