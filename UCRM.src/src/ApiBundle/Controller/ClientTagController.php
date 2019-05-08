<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Component\Validator\Validator;
use ApiBundle\Map\ClientTagMap;
use ApiBundle\Mapper\ClientTagMapper;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\ClientTagController as AppClientTagController;
use AppBundle\DataProvider\ClientTagDataProvider;
use AppBundle\Entity\ClientTag;
use AppBundle\Facade\ClientTagFacade;
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

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(AppClientTagController::class)
 */
class ClientTagController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var ClientTagFacade
     */
    private $facade;

    /**
     * @var ClientTagMapper
     */
    private $mapper;

    /**
     * @var ClientTagDataProvider
     */
    private $dataProvider;

    public function __construct(Validator $validator, ClientTagFacade $facade, ClientTagMapper $mapper, ClientTagDataProvider $dataProvider)
    {
        $this->validator = $validator;
        $this->facade = $facade;
        $this->mapper = $mapper;
        $this->dataProvider = $dataProvider;
    }

    /**
     * @Get("/client-tags/{id}", name="client_tag_get", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(ClientTag $clientTag): View
    {
        return $this->view(
            $this->mapper->reflect($clientTag)
        );
    }

    /**
     * @Get("/client-tags", name="client_tag_collection_get", options={"method_prefix"=false})
     * @ViewHandler()
     * @Permission("view")
     */
    public function getCollectionAction(): View
    {
        $clients = $this->dataProvider->getClientTags();

        return $this->view(
            $this->mapper->reflectCollection($clients)
        );
    }

    /**
     * @Post("/client-tags", name="client_tag_add", options={"method_prefix"=false})
     * @ParamConverter("clientTagMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function postAction(ClientTagMap $clientTagMap, string $version): View
    {
        $clientTag = new ClientTag();
        $this->mapper->map($clientTagMap, $clientTag);
        $this->validator->validate($clientTag, $this->mapper->getFieldsDifference());
        $this->facade->handleCreate($clientTag);

        return $this->routeRedirectViewWithData(
            $this->mapper->reflect($clientTag),
            'api_client_tag_get',
            [
                'version' => $version,
                'id' => $clientTag->getId(),
            ]
        );
    }

    /**
     * @Patch("/client-tags/{id}", name="client_tag_edit", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ParamConverter("clientTagMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function patchAction(ClientTag $clientTag, ClientTagMap $clientTagMap): View
    {
        $this->mapper->map($clientTagMap, $clientTag);
        $this->validator->validate($clientTag, $this->mapper->getFieldsDifference());
        $this->facade->handleUpdate($clientTag);

        return $this->view(
            $this->mapper->reflect($clientTag)
        );
    }

    /**
     * @Delete(
     *     "/client-tags/{id}",
     *     name="client_tag_delete",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("public")
     */
    public function deleteAction(ClientTag $clientTag): View
    {
        $this->facade->handleDelete($clientTag);

        return $this->view(null, 200);
    }
}
