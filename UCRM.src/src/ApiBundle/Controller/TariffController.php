<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Component\Validator\Validator;
use ApiBundle\Map\TariffMap;
use ApiBundle\Mapper\TariffMapper;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\TariffController as AppTariffController;
use AppBundle\Entity\Tariff;
use AppBundle\Facade\TariffFacade;
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
 * @PermissionControllerName(AppTariffController::class)
 */
class TariffController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var TariffFacade
     */
    private $facade;

    /**
     * @var TariffMapper
     */
    private $mapper;

    public function __construct(Validator $validator, TariffFacade $facade, TariffMapper $mapper)
    {
        $this->validator = $validator;
        $this->facade = $facade;
        $this->mapper = $mapper;
    }

    /**
     * @Get("/service-plans/{id}", name="tariff_get", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(Tariff $tariff): View
    {
        $this->notDeleted($tariff);

        return $this->view(
            $this->mapper->reflect($tariff)
        );
    }

    /**
     * @Delete(
     *     "/service-plans/{id}",
     *     name="tariff_delete",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("edit")
     */
    public function deleteAction(Tariff $tariff): View
    {
        $this->notDeleted($tariff);

        if (! $this->facade->handleDelete($tariff)) {
            throw new HttpException(422, 'Service plan could not be deleted.');
        }

        return $this->view(null, 200);
    }

    /**
     * @Get("/service-plans", name="tariff_collection_get", options={"method_prefix"=false})
     * @ViewHandler()
     * @Permission("view")
     */
    public function getCollectionAction(): View
    {
        $tariffs = $this->facade->getAll();

        return $this->view(
            $this->mapper->reflectCollection($tariffs)
        );
    }

    /**
     * @Post("/service-plans", name="tariff_add", options={"method_prefix"=false})
     * @ParamConverter("tariffMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function postAction(TariffMap $tariffMap, string $version): View
    {
        $tariff = new Tariff();
        $this->facade->setDefaults($tariff);
        $this->mapper->map($tariffMap, $tariff);
        $this->validator->validate($tariff, $this->mapper->getFieldsDifference());
        $this->facade->handleCreate($tariff);

        return $this->routeRedirectViewWithData(
            $this->mapper->reflect($tariff),
            'api_tariff_get',
            [
                'version' => $version,
                'id' => $tariff->getId(),
            ]
        );
    }

    /**
     * @Patch(
     *     "/service-plans/{id}",
     *     name="tariff_edit",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ParamConverter("tariffMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function patchAction(Tariff $tariff, TariffMap $tariffMap): View
    {
        $tariffBeforeUpdate = clone $tariff;
        $this->mapper->map($tariffMap, $tariff);
        $this->validator->validate($tariff, $this->mapper->getFieldsDifference());
        $this->facade->handleUpdate($tariff, $tariffBeforeUpdate);

        return $this->view(
            $this->mapper->reflect($tariff)
        );
    }
}
