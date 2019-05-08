<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Component\Validator\Validator;
use ApiBundle\Map\ClientContactMap;
use ApiBundle\Mapper\ClientContactMapper;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\ClientController;
use AppBundle\Entity\Client;
use AppBundle\Entity\ClientContact;
use AppBundle\Facade\ClientContactFacade;
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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(ClientController::class)
 */
class ClientContactController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var ClientContactFacade
     */
    private $facade;

    /**
     * @var ClientContactMapper
     */
    private $mapper;

    public function __construct(
        Validator $validator,
        ClientContactFacade $facade,
        ClientContactMapper $mapper
    ) {
        $this->validator = $validator;
        $this->facade = $facade;
        $this->mapper = $mapper;
    }

    /**
     * @Get(
     *     "/clients/contacts/{id}",
     *     name="client_contact_get",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(ClientContact $clientContact): View
    {
        return $this->view(
            $this->mapper->reflect($clientContact)
        );
    }

    /**
     * @Get(
     *     "/clients/{id}/contacts",
     *     name="client_contacts_collection_get",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("view")
     */
    public function getCollectionAction(Client $client): View
    {
        $clientContacts = $client->getContacts();

        return $this->view(
            $this->mapper->reflectCollection($clientContacts)
        );
    }

    /**
     * @Post(
     *     "/clients/{id}/contacts",
     *     name="client_contacts_add",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ParamConverter("clientContactMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function postAction(Client $client, ClientContactMap $clientContactMap, string $version): View
    {
        if ($client->isDeleted()) {
            throw new NotFoundHttpException('Client is archived. All actions are prohibited. You can only restore the client.');
        }

        $clientContact = new ClientContact();
        $this->facade->setDefaults($client, $clientContact);
        $this->mapper->map($clientContactMap, $clientContact);
        $this->validator->validate($clientContact, $this->mapper->getFieldsDifference());
        $this->facade->handleCreate($clientContact);

        return $this->routeRedirectViewWithData(
            $this->mapper->reflect($clientContact),
            'api_client_contact_get',
            [
                'version' => $version,
                'id' => $clientContact->getId(),
            ]
        );
    }

    /**
     * @Patch(
     *     "/clients/contacts/{id}",
     *     name="client_contact_edit",
     *     options={"method_prefix" = false},
     *     requirements={"id" = "\d+"}
     * )
     * @ParamConverter("clientContactMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function patchAction(ClientContact $clientContact, ClientContactMap $clientContactMap): View
    {
        if ($clientContact->getClient()->isDeleted()) {
            throw new NotFoundHttpException('Client is archived. All actions are prohibited. You can only restore the client.');
        }

        $this->mapper->map($clientContactMap, $clientContact);
        $this->validator->validate($clientContact, $this->mapper->getFieldsDifference());
        $this->facade->handleUpdate($clientContact);

        return $this->view(
            $this->mapper->reflect($clientContact)
        );
    }

    /**
     * @Delete(
     *     "/clients/contacts/{id}",
     *     name="client_contact_delete",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("edit")
     */
    public function deleteAction(ClientContact $clientContact): View
    {
        if ($clientContact->getClient()->isDeleted()) {
            throw new NotFoundHttpException('Client is archived. All actions are prohibited. You can only restore the client.');
        }

        $this->facade->handleDelete($clientContact);

        return $this->view(null, 200);
    }
}
