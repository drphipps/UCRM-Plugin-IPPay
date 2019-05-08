<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Entity\Client;
use AppBundle\Entity\Product;
use AppBundle\Facade\ProductFacade;
use AppBundle\Form\ProductType;
use AppBundle\Grid\Product\ProductGridFactory;
use AppBundle\Security\Permission;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/items/products")
 */
class ProductController extends BaseController
{
    /**
     * @Route("", name="product_index")
     * @Method("GET")
     * @Permission("view")
     * @Searchable(heading="Products", path="System -> Service plans & Products -> Products")
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(ProductGridFactory::class)->create();
        if ($response = $grid->processMultiAction()) {
            return $response;
        }
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'product/index.html.twig',
            [
                'productsGrid' => $grid,
            ]
        );
    }

    /**
     * @Route("/new", name="product_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request): Response
    {
        return $this->handleNewEditAction($request);
    }

    /**
     * @Route("/new-modal/{id}", name="product_new_modal", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newModalAction(Request $request, Client $client): Response
    {
        $product = new Product();
        $url = $this->generateUrl('product_new_modal', ['id' => $client->getId()]);
        $form = $this->createForm(ProductType::class, $product, ['action' => $url]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($product);
            $this->em->flush();

            $this->addTranslatedFlash('success', 'Product has been created.');

            return $this->createAjaxResponse(
                [
                    'product' => [
                        'path' => $this->generateUrl(
                            'financial_item_product',
                            [
                                'clientId' => $client->getId(),
                                'id' => $product->getId(),
                            ]
                        ),
                        'name' => $product->getName(),
                        'name_lower' => mb_strtolower($product->getName()),
                    ],
                ]
            );
        }

        return $this->render(
            'product/new_modal.html.twig',
            [
                'form' => $form->createView(),
                'product' => $product,
            ]
        );
    }

    /**
     * @Route("/{id}", name="product_show", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showAction(Product $product): Response
    {
        return $this->render(
            'product/show.html.twig',
            [
                'product' => $product,
            ]
        );
    }

    /**
     * @Route("/{id}/edit", name="product_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, Product $product): Response
    {
        $this->notDeleted($product);

        return $this->handleNewEditAction($request, $product);
    }

    /**
     * @Route("/{id}/delete", name="product_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(Product $product): Response
    {
        $this->notDeleted($product);

        if (! $this->get(ProductFacade::class)->handleDelete($product)) {
            $this->addTranslatedFlash('error', 'Product could not be deleted.');

            return $this->redirectToRoute(
                'product_show',
                [
                    'id' => $product->getId(),
                ]
            );
        }

        $this->addTranslatedFlash('success', 'Product has been deleted.');

        return $this->redirectToRoute('product_index');
    }

    private function handleNewEditAction(Request $request, Product $product = null): Response
    {
        $isEdit = (bool) $product;
        if (! $isEdit) {
            $product = new Product();
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $productFacade = $this->get(ProductFacade::class);

            if ($isEdit) {
                $productFacade->handleUpdate($product);
                $this->addTranslatedFlash('success', 'Product has been saved.');
            } else {
                $productFacade->handleCreate($product);
                $this->addTranslatedFlash('success', 'Product has been created.');
            }

            return $this->redirectToRoute('product_show', ['id' => $product->getId()]);
        }

        return $this->render(
            'product/edit.html.twig',
            [
                'form' => $form->createView(),
                'isEdit' => $isEdit,
                'product' => $product,
            ]
        );
    }
}
