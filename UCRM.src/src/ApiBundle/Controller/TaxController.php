<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Component\Validator\Validator;
use ApiBundle\Map\TaxMap;
use ApiBundle\Mapper\TaxMapper;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\TaxController as AppTaxController;
use AppBundle\Entity\Tax;
use AppBundle\Facade\TaxFacade;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(AppTaxController::class)
 */
class TaxController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var TaxFacade
     */
    private $facade;

    /**
     * @var TaxMapper
     */
    private $mapper;

    public function __construct(Validator $validator, TaxFacade $facade, TaxMapper $mapper)
    {
        $this->validator = $validator;
        $this->facade = $facade;
        $this->mapper = $mapper;
    }

    /**
     * @Get(
     *     "/taxes/{id}",
     *     name="tax_get",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(Tax $tax): View
    {
        $this->notDeleted($tax);

        return $this->view(
            $this->mapper->reflect($tax)
        );
    }

    /**
     * @Delete(
     *     "/taxes/{id}",
     *     name="tax_delete",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("edit")
     */
    public function deleteAction(Tax $tax): View
    {
        try {
            $this->facade->handleDelete($tax);
        } catch (ForeignKeyConstraintViolationException  $e) {
            throw new HttpException(422, 'Cannot be deleted. Item is used.');
        }

        return $this->view(null, 200);
    }

    /**
     * @Get("/taxes", name="tax_collection_get", options={"method_prefix"=false})
     * @ViewHandler()
     * @Permission("view")
     */
    public function getCollectionAction(): View
    {
        $taxes = $this->facade->getAllTaxes();

        return $this->view(
            $this->mapper->reflectCollection($taxes)
        );
    }

    /**
     * @Post("/taxes", name="tax_add", options={"method_prefix"=false})
     * @ParamConverter("taxMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function postAction(TaxMap $taxMap, string $version): View
    {
        $tax = new Tax();
        $this->mapper->map($taxMap, $tax);
        $this->validator->validate($tax, $this->mapper->getFieldsDifference());
        $this->facade->handleCreate($tax);

        return $this->routeRedirectViewWithData(
            $this->mapper->reflect($tax),
            'api_tax_get',
            [
                'version' => $version,
                'id' => $tax->getId(),
            ]
        );
    }
}
