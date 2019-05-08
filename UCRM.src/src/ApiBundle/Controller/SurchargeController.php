<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Component\Validator\Validator;
use ApiBundle\Map\SurchargeMap;
use ApiBundle\Mapper\SurchargeMapper;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\SurchargeController as AppSurchargeController;
use AppBundle\Entity\Surcharge;
use AppBundle\Facade\SurchargeFacade;
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
 * @PermissionControllerName(AppSurchargeController::class)
 */
class SurchargeController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var SurchargeFacade
     */
    private $facade;

    /**
     * @var SurchargeMapper
     */
    private $mapper;

    public function __construct(Validator $validator, SurchargeFacade $facade, SurchargeMapper $mapper)
    {
        $this->validator = $validator;
        $this->facade = $facade;
        $this->mapper = $mapper;
    }

    /**
     * @Get(
     *     "/surcharges/{id}",
     *     name="surcharge_get",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(Surcharge $surcharge): View
    {
        $this->notDeleted($surcharge);

        return $this->view(
            $this->mapper->reflect($surcharge)
        );
    }

    /**
     * @Delete(
     *     "/surcharges/{id}",
     *     name="surcharge_delete",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("edit")
     */
    public function deleteAction(Surcharge $surcharge): View
    {
        $this->notDeleted($surcharge);

        if (! $this->facade->handleDelete($surcharge)) {
            throw new HttpException(422, 'Surcharge could not be deleted.');
        }

        return $this->view(null, 200);
    }

    /**
     * @Get("/surcharges", name="surcharge_collection_get", options={"method_prefix"=false})
     * @ViewHandler()
     * @Permission("view")
     */
    public function getCollectionAction(): View
    {
        $surcharges = $this->facade->getAllSurcharges();

        return $this->view(
            $this->mapper->reflectCollection($surcharges)
        );
    }

    /**
     * @Post("/surcharges", name="surcharge_add", options={"method_prefix"=false})
     * @ParamConverter("surchargeMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function postAction(SurchargeMap $surchargeMap, string $version): View
    {
        $surcharge = new Surcharge();
        $this->mapper->map($surchargeMap, $surcharge);
        $this->validator->validate($surcharge, $this->mapper->getFieldsDifference());
        $this->facade->handleCreate($surcharge);

        return $this->routeRedirectViewWithData(
            $this->mapper->reflect($surcharge),
            'api_surcharge_get',
            [
                'version' => $version,
                'id' => $surcharge->getId(),
            ]
        );
    }

    /**
     * @Patch(
     *     "/surcharges/{id}",
     *     name="surcharge_edit",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ParamConverter("surchargeMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function patchAction(Surcharge $surcharge, SurchargeMap $surchargeMap): View
    {
        $surchargeBeforeUpdate = clone $surcharge;
        $this->mapper->map($surchargeMap, $surcharge);
        $this->validator->validate($surcharge, $this->mapper->getFieldsDifference());
        $this->facade->handleUpdate($surcharge, $surchargeBeforeUpdate);

        return $this->view(
            $this->mapper->reflect($surcharge)
        );
    }
}
