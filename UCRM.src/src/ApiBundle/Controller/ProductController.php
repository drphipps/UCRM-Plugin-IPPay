<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Component\Validator\Validator;
use ApiBundle\Map\ProductMap;
use ApiBundle\Mapper\ProductMapper;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\ProductController as AppProductController;
use AppBundle\Entity\Product;
use AppBundle\Facade\ProductFacade;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(AppProductController::class)
 */
class ProductController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var ProductFacade
     */
    private $facade;

    /**
     * @var ProductMapper
     */
    private $mapper;

    public function __construct(Validator $validator, ProductFacade $facade, ProductMapper $mapper)
    {
        $this->validator = $validator;
        $this->facade = $facade;
        $this->mapper = $mapper;
    }

    /**
     * @Get("/products/{id}", name="product_get", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(Product $product): View
    {
        $this->notDeleted($product);

        return $this->view(
            $this->mapper->reflect($product)
        );
    }

    /**
     * @Get("/products", name="product_collection_get", options={"method_prefix"=false})
     * @ViewHandler()
     * @Permission("view")
     */
    public function getCollectionAction(): View
    {
        $products = $this->facade->getAllProducts();

        return $this->view(
            $this->mapper->reflectCollection($products)
        );
    }

    /**
     * @Post("/products", name="product_add", options={"method_prefix"=false})
     * @ParamConverter("productMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function postAction(ProductMap $productMap, string $version): View
    {
        $product = new Product();
        $this->mapper->map($productMap, $product);
        $this->validator->validate($product, $this->mapper->getFieldsDifference());
        $this->facade->handleCreate($product);

        return $this->routeRedirectViewWithData(
            $this->mapper->reflect($product),
            'api_product_get',
            [
                'version' => $version,
                'id' => $product->getId(),
            ]
        );
    }

    /**
     * @Patch(
     *     "/products/{id}",
     *     name="product_edit",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ParamConverter("productMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function patchAction(Product $product, ProductMap $productMap): View
    {
        $this->mapper->map($productMap, $product);
        $this->validator->validate($product, $this->mapper->getFieldsDifference());
        $this->facade->handleUpdate($product);

        return $this->view(
            $this->mapper->reflect($product)
        );
    }

    /**
     * @Delete(
     *     "/products/{id}",
     *     name="product_delete",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("edit")
     */
    public function deleteAction(Product $product): View
    {
        $this->notDeleted($product);

        if (! $this->facade->handleDelete($product)) {
            throw new HttpException(422, 'Product could not be deleted.');
        }

        return $this->view(null, 200);
    }
}
