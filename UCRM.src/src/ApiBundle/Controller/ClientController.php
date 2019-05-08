<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Component\Validator\ValidationHttpException;
use ApiBundle\Component\Validator\Validator;
use ApiBundle\Map\ClientAuthenticateMap;
use ApiBundle\Map\ClientEditMap;
use ApiBundle\Mapper\ClientEditMapper;
use ApiBundle\Mapper\ClientMapper;
use ApiBundle\Request\ClientCollectionRequest;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\ClientController as AppClientController;
use AppBundle\DataProvider\ClientDataProvider;
use AppBundle\Entity\Client;
use AppBundle\Entity\ClientTag;
use AppBundle\Exception\NoClientContactException;
use AppBundle\Facade\ClientFacade;
use AppBundle\Facade\Exception\CannotCancelClientSubscriptionException;
use AppBundle\Facade\Exception\CannotDeleteDemoClientException;
use AppBundle\Factory\ClientFactory;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Service\InvitationEmailSender;
use AppBundle\Util\Helpers;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(AppClientController::class)
 */
class ClientController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var ClientFacade
     */
    private $facade;

    /**
     * @var ClientMapper
     */
    private $mapper;

    /**
     * @var ClientDataProvider
     */
    private $dataProvider;

    /**
     * @var ClientEditMapper
     */
    private $editMapper;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    public function __construct(
        Validator $validator,
        ClientFacade $facade,
        ClientMapper $mapper,
        ClientDataProvider $dataProvider,
        ClientEditMapper $editMapper,
        ClientFactory $clientFactory
    ) {
        $this->validator = $validator;
        $this->facade = $facade;
        $this->mapper = $mapper;
        $this->dataProvider = $dataProvider;
        $this->editMapper = $editMapper;
        $this->clientFactory = $clientFactory;
    }

    /**
     * @Get("/clients/{id}", name="client_get", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(Client $client): View
    {
        return $this->view(
            $this->mapper->reflect($client)
        );
    }

    /**
     * @Get("/clients", name="client_collection_get", options={"method_prefix"=false})
     * @ViewHandler()
     * @Permission("view")
     * @QueryParam(
     *     name="organizationId",
     *     requirements="\d+",
     *     strict=true,
     *     nullable=true,
     *     description="organization ID"
     * )
     * @QueryParam(
     *     name="userIdent",
     *     nullable=true,
     *     description="search by custom identifier"
     * )
     * @QueryParam(
     *     name="customAttributeKey",
     *     nullable=true,
     *     description="search by custom attribute, you have to specify customAttributeValue as well"
     * )
     * @QueryParam(
     *     name="customAttributeValue",
     *     nullable=true
     * )
     * @QueryParam(
     *     name="order",
     *     requirements="user\.firstName|user\.lastName|client\.registrationDate|client\.id",
     *     strict=true,
     *     nullable=true,
     *     description="order by (user.firstName|user.lastName|client.registrationDate|client.id)"
     * )
     * @QueryParam(
     *     name="direction",
     *     requirements="ASC|DESC",
     *     strict=true,
     *     nullable=true,
     *     description="direction of sort - ascending (ASC) or descending (DESC)"
     * )
     * @QueryParam(
     *     name="lead",
     *     requirements="[01]",
     *     strict=true,
     *     nullable=true,
     *     description="filter lead clients"
     * )
     * @QueryParam(
     *     name="limit",
     *     requirements="\d+",
     *     strict=true,
     *     nullable=true,
     *     description="max results limit"
     * )
     * @QueryParam(
     *     name="offset",
     *     requirements="\d+",
     *     strict=true,
     *     nullable=true,
     *     description="results offset"
     * )
     */
    public function getCollectionAction(ParamFetcherInterface $paramFetcher): View
    {
        $customAttributeKey = $paramFetcher->get('customAttributeKey', true);
        $customAttributeValue = $paramFetcher->get('customAttributeValue', true);

        if ($customAttributeKey === null xor $customAttributeValue === null) {
            throw new ValidationHttpException(
                [],
                'You have to specify both customAttributeKey and customAttributeValue.'
            );
        }

        $request = new ClientCollectionRequest();

        $request->organizationId = Helpers::typeCastNullable('int', $paramFetcher->get('organizationId'));
        $request->userIdent = $paramFetcher->get('userIdent', true);
        $request->order = $paramFetcher->get('order', true);
        $request->direction = $paramFetcher->get('direction', true);
        $request->isLead = Helpers::typeCastNullable('bool', $paramFetcher->get('lead'));
        $request->limit = Helpers::typeCastNullable('int', $paramFetcher->get('limit'));
        $request->offset = Helpers::typeCastNullable('int', $paramFetcher->get('offset'));

        if ($customAttributeKey) {
            $request->matchByCustomAttribute($customAttributeKey, $customAttributeValue);
        }

        $clients = $this->dataProvider->getClientCollection($request);

        return $this->view(
            $this->mapper->reflectCollection($clients)
        );
    }

    /**
     * @Post("/clients", name="client_add", options={"method_prefix"=false})
     * @ParamConverter("clientEditMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function postAction(ClientEditMap $clientEditMap, string $version): View
    {
        $client = $this->clientFactory->create();
        $this->editMapper->map($clientEditMap, $client);
        $this->validator->validate($client, $this->editMapper->getFieldsDifference());
        $this->facade->handleCreate($client);

        return $this->routeRedirectViewWithData(
            $this->mapper->reflect($client),
            'api_client_get',
            [
                'version' => $version,
                'id' => $client->getId(),
            ]
        );
    }

    /**
     * @Patch("/clients/{id}", name="client_edit", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ParamConverter("clientEditMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function patchAction(Client $client, ClientEditMap $clientEditMap): View
    {
        $this->validateIsNotDeleted($client);

        $clientBeforeUpdate = clone $client;
        $this->editMapper->map($clientEditMap, $client);
        $this->validator->validate($client, $this->editMapper->getFieldsDifference());
        $this->facade->handleUpdate($client, $clientBeforeUpdate);

        return $this->view(
            $this->mapper->reflect($client)
        );
    }

    /**
     * @Patch(
     *     "/clients/{id}/add-tag/{clientTag}",
     *     name="client_add_tag",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+", "clientTag": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("edit")
     */
    public function addTagAction(Client $client, ClientTag $clientTag): View
    {
        if ($client->isDeleted()) {
            throw new NotFoundHttpException(
                'Client is archived. All actions are prohibited. You can only restore the client.'
            );
        }

        $clientBeforeUpdate = clone $client;

        $client->addClientTag($clientTag);

        $this->facade->handleUpdate($client, $clientBeforeUpdate);

        return $this->view(
            $this->mapper->reflect($client)
        );
    }

    /**
     * @Patch(
     *     "/clients/{id}/remove-tag/{clientTag}",
     *     name="client_remove_tag",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+", "clientTag": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("edit")
     */
    public function removeTagAction(Client $client, ClientTag $clientTag): View
    {
        if ($client->isDeleted()) {
            throw new NotFoundHttpException(
                'Client is archived. All actions are prohibited. You can only restore the client.'
            );
        }

        $clientBeforeUpdate = clone $client;

        $client->removeClientTag($clientTag);

        $this->facade->handleUpdate($client, $clientBeforeUpdate);

        return $this->view(
            $this->mapper->reflect($client)
        );
    }

    /**
     * @Patch("/clients/{id}/send-invitation", name="client_invite", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ViewHandler()
     * @Permission("edit")
     */
    public function sendInvitationAction(Client $client): View
    {
        $this->validateIsNotDeleted($client);

        try {
            $this->get(InvitationEmailSender::class)->send($client);
        } catch (NoClientContactException $exception) {
            throw new HttpException(422, $exception->getMessage());
        }

        return $this->view(
            $this->mapper->reflect($client)
        );
    }

    /**
     * @Patch(
     *     "/clients/{id}/archive",
     *     name="client_archive",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("edit")
     */
    public function archiveAction(Client $client): View
    {
        if ($client->isDeleted()) {
            throw new HttpException(422, 'Client is already archived.');
        }

        if (Helpers::isDemo() && $client->getUser()->getUsername() === ClientFacade::DEMO_CLIENT_USERNAME) {
            throw new HttpException(422, 'This client cannot be deleted in demo.');
        }

        $this->facade->handleArchive($client);

        return $this->view(null, 200);
    }

    /**
     * @Delete(
     *     "/clients/{id}",
     *     name="client_delete",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("edit")
     */
    public function deleteAction(Client $client): View
    {
        try {
            $this->facade->handleDelete($client);
        } catch (CannotDeleteDemoClientException $exception) {
            throw new HttpException(422, 'This client cannot be deleted in demo.');
        } catch (CannotCancelClientSubscriptionException $exception) {
            throw new HttpException(
                422, sprintf(
                    'Failed to cancel subscription "%s".',
                    $exception->getPaymentPlan()->getName()
                )
            );
        }

        return $this->view(null, 200);
    }

    /**
     * @Post("/clients/authenticated", name="clients_authenticated", options={"method_prefix"=false})
     * @ViewHandler()
     * @Permission("view")
     * @ParamConverter("map", converter="fos_rest.request_body")
     */
    public function getAuthenticatedAction(ClientAuthenticateMap $map): View
    {
        $this->validator->validate($map);

        $client = $this->dataProvider->findByUsernamePassword($map->username, $map->password);

        if ($client === null) {
            throw new NotFoundHttpException();
        }

        return $this->view(
            $this->mapper->reflect($client)
        );
    }

    /**
     * @Patch("/clients/{id}/geocode", name="client_geocode", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ViewHandler()
     * @Permission("edit")
     */
    public function geocodeAction(Client $client)
    {
        $this->validateIsNotDeleted($client);

        try {
            $this->facade->geocode($client);
        } catch (\RuntimeException $exception) {
            throw new HttpException(422, $exception->getMessage());
        }

        return $this->view(
            $this->mapper->reflect($client)
        );
    }

    private function validateIsNotDeleted(Client $client): void
    {
        if ($client->isDeleted()) {
            throw new NotFoundHttpException(
                'Client is archived. All actions are prohibited. You can only restore the client.'
            );
        }
    }
}
