<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Api\Controller;

use ApiBundle\Component\Validator\Validator;
use ApiBundle\Controller\BaseController;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Entity\Client;
use AppBundle\Entity\User;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Util\DateTimeFactory;
use AppBundle\Util\Helpers;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use SchedulingBundle\Entity\Job;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints as Assert;
use TicketingBundle\Api\Map\TicketMap;
use TicketingBundle\Api\Mapper\TicketMapper;
use TicketingBundle\Api\Request\TicketCollectionRequest;
use TicketingBundle\Controller\TicketController as AppTicketController;
use TicketingBundle\DataProvider\TicketDataProvider;
use TicketingBundle\Entity\Ticket;
use TicketingBundle\Entity\TicketGroup;
use TicketingBundle\Service\Facade\TicketFacade;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(AppTicketController::class)
 */
class TicketController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var TicketFacade
     */
    private $facade;

    /**
     * @var TicketMapper
     */
    private $mapper;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var TicketDataProvider
     */
    private $dataProvider;

    public function __construct(
        Validator $validator,
        TicketFacade $facade,
        TicketMapper $mapper,
        EntityManagerInterface $em,
        TicketDataProvider $dataProvider
    ) {
        $this->validator = $validator;
        $this->facade = $facade;
        $this->mapper = $mapper;
        $this->em = $em;
        $this->dataProvider = $dataProvider;
    }

    /**
     * @Get("/ticketing/tickets/{id}", name="ticket_get", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(Ticket $ticket): View
    {
        return $this->view(
            $this->mapper->reflect($ticket)
        );
    }

    /**
     * @Get("/ticketing/tickets", name="ticket_collection_get", options={"method_prefix"=false})
     * @ViewHandler()
     * @Permission("view")
     * @QueryParam(
     *     name="dateFrom",
     *     requirements="\d{4}-\d{2}-\d{2}",
     *     strict=true,
     *     nullable=true,
     *     description="limit collection starting on date (including)"
     * )
     * @QueryParam(
     *     name="dateTo",
     *     requirements="\d{4}-\d{2}-\d{2}",
     *     strict=true,
     *     nullable=true,
     *     description="limit collection ending on date (including)"
     * )
     * @QueryParam(
     *     name="clientId",
     *     requirements="\d+|null",
     *     strict=true,
     *     nullable=true,
     *     description="client ID"
     * )
     * @QueryParam(
     *     name="assignedUserId",
     *     requirements="\d+|null",
     *     strict=true,
     *     nullable=true,
     *     description="assigned user ID"
     * )
     * @QueryParam(
     *     name="assignedGroupId",
     *     requirements="\d+|null",
     *     strict=true,
     *     nullable=true,
     *     description="assigned group ID"
     * )
     * @QueryParam(
     *     name="statuses",
     *     requirements=@Assert\All(@Assert\Choice(Ticket::STATUSES_NUMERIC)),
     *     strict=true,
     *     nullable=true,
     *     description="select only tickets in one of the given statuses"
     * )
     * @QueryParam(
     *     name="public",
     *     requirements="[01]",
     *     strict=true,
     *     nullable=true,
     *     description="filter public tickets"
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
     * @QueryParam(
     *     name="order",
     *     requirements="createdAt|id|lastActivity",
     *     strict=true,
     *     nullable=true,
     *     description="order by (createdAt|id|lastActivity)"
     * )
     * @QueryParam(
     *     name="direction",
     *     requirements="ASC|DESC",
     *     strict=true,
     *     nullable=true,
     *     description="direction of sort - ascending (ASC) or descending (DESC)"
     * )
     */
    public function getCollectionAction(ParamFetcherInterface $paramFetcher): View
    {
        $ticketCollectionRequest = new TicketCollectionRequest();

        $filterNullRelations = [];

        if ($clientId = $paramFetcher->get('clientId')) {
            if ($clientId === 'null') {
                $filterNullRelations[] = 'client';
            } else {
                $client = $this->em->find(Client::class, $clientId);
                if (! $client) {
                    throw new NotFoundHttpException('Client object not found.');
                }

                if ($client->isDeleted()) {
                    throw new NotFoundHttpException(
                        'Client is archived. All actions are prohibited. You can only restore the client.'
                    );
                }
                $ticketCollectionRequest->client = $client;
            }
        }

        if ($assignedUserId = $paramFetcher->get('assignedUserId')) {
            if ($assignedUserId === 'null') {
                $filterNullRelations[] = 'assignedUser';
            } else {
                $user = $this->em->getRepository(User::class)->findOneBy(
                    [
                        'id' => $assignedUserId,
                        'role' => User::ADMIN_ROLES,
                    ]
                );
                if (! $assignedUserId) {
                    throw new NotFoundHttpException('User object not found.');
                }
                $ticketCollectionRequest->user = $user;
            }
        }

        if ($assignedGroupId = $paramFetcher->get('assignedGroupId')) {
            if ($assignedGroupId === 'null') {
                $filterNullRelations[] = 'assignedGroup';
            } else {
                $ticketGroup = $this->em->find(TicketGroup::class, $assignedGroupId);
                if (! $assignedGroupId) {
                    throw new NotFoundHttpException('Ticket group object not found.');
                }
                $ticketCollectionRequest->ticketGroup = $ticketGroup;
            }
        }

        $ticketCollectionRequest->filterNullRelations = $filterNullRelations;

        if ($startDate = $paramFetcher->get('dateFrom')) {
            try {
                $ticketCollectionRequest->startDate = DateTimeFactory::createDate($startDate);
            } catch (\InvalidArgumentException $exception) {
                throw new HttpException(400, $exception->getMessage(), $exception);
            }
        }
        if ($endDate = $paramFetcher->get('dateTo')) {
            try {
                $ticketCollectionRequest->endDate = DateTimeFactory::createDate($endDate)->setTime(23, 59, 59);
            } catch (\InvalidArgumentException $exception) {
                throw new HttpException(400, $exception->getMessage(), $exception);
            }
        }
        if ($statuses = $paramFetcher->get('statuses')) {
            $ticketCollectionRequest->statuses = Helpers::typeCastAll('int', $statuses);
        }

        $ticketCollectionRequest->public = Helpers::typeCastNullable('bool', $paramFetcher->get('public'));

        if ($limit = $paramFetcher->get('limit')) {
            $ticketCollectionRequest->limit = Helpers::typeCast('int', $limit);
        }
        if ($offset = $paramFetcher->get('offset')) {
            $ticketCollectionRequest->offset = Helpers::typeCast('int', $offset);
        }
        $ticketCollectionRequest->order = $paramFetcher->get('order', true);
        $ticketCollectionRequest->direction = $paramFetcher->get('direction', true);

        return $this->view(
            $this->mapper->reflectCollection(
                $this->dataProvider->getTicketsAPI($ticketCollectionRequest)
            )
        );
    }

    /**
     * @Post("/ticketing/tickets", name="ticket_add", options={"method_prefix"=false})
     * @ParamConverter("ticketMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function postAction(TicketMap $ticketMap, string $version): View
    {
        $ticket = new Ticket();
        $this->mapper->map($ticketMap, $ticket);
        $validationGroups = ['Default', 'Api'];
        $this->validator->validate($ticket, $this->mapper->getFieldsDifference(), null, $validationGroups);

        $this->facade->handleNewFromAPI($ticket, $ticketMap);

        return $this->routeRedirectViewWithData(
            $this->mapper->reflect($ticket),
            'api_ticket_get',
            [
                'version' => $version,
                'id' => $ticket->getId(),
            ]
        );
    }

    /**
     * @Patch(
     *     "/ticketing/tickets/{id}",
     *     name="ticket_edit",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ParamConverter("ticketMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function patchAction(Ticket $ticket, TicketMap $ticketMap): View
    {
        $ticketBeforeUpdate = clone $ticket;
        $this->mapper->map($ticketMap, $ticket);
        $validationGroups = ['Default', 'Api'];
        $this->validator->validate($ticket, $this->mapper->getFieldsDifference(), null, $validationGroups);
        $this->facade->handleEdit($ticket, $ticketBeforeUpdate);

        return $this->view(
            $this->mapper->reflect($ticket)
        );
    }

    /**
     * @Delete(
     *     "/ticketing/tickets/{id}",
     *     name="ticket_delete",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("edit")
     */
    public function deleteAction(Ticket $ticket): View
    {
        $this->facade->handleDelete($ticket);

        return $this->view(null, 200);
    }

    /**
     * @Patch(
     *     "/ticketing/tickets/{id}/add-job/{job}",
     *     name="ticket_add_job",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+", "job": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("edit")
     */
    public function addJobAction(Ticket $ticket, Job $job): View
    {
        $this->facade->handleAddJob($ticket, $job);

        return $this->view(
            $this->mapper->reflect($ticket)
        );
    }

    /**
     * @Patch(
     *     "/ticketing/tickets/{id}/remove-job/{job}",
     *     name="ticket_remove_job",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+", "job": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("edit")
     */
    public function removeJobAction(Ticket $ticket, Job $job): View
    {
        $this->facade->handleRemoveJob($ticket, $job);

        return $this->view(
            $this->mapper->reflect($ticket)
        );
    }
}
