<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 *
 * @see https://www.ubnt.com/
 */
declare(strict_types=1);

namespace TicketingBundle\Api\Controller;

use ApiBundle\Component\Validator\Validator;
use ApiBundle\Controller\BaseController;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Entity\User;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Util\DateTimeFactory;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use TicketingBundle\Api\Mapper\TicketActivityMapper;
use TicketingBundle\Controller\TicketController as AppTicketController;
use TicketingBundle\DataProvider\TicketActivityDataProvider;
use TicketingBundle\Entity\Ticket;
use TicketingBundle\Entity\TicketActivity;
use TicketingBundle\Service\Facade\TicketCommentFacade;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(AppTicketController::class)
 */
class TicketActivityController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var TicketCommentFacade
     */
    private $facade;

    /**
     * @var TicketActivityMapper
     */
    private $mapper;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var TicketActivityDataProvider
     */
    private $dataProvider;

    public function __construct(
        Validator $validator,
        TicketCommentFacade $facade,
        TicketActivityMapper $mapper,
        EntityManagerInterface $em,
        TicketActivityDataProvider $dataProvider
    ) {
        $this->validator = $validator;
        $this->facade = $facade;
        $this->mapper = $mapper;
        $this->em = $em;
        $this->dataProvider = $dataProvider;
    }

    /**
     * @Get(
     *     "/ticketing/tickets/activities/{id}",
     *     name="ticket_activity_get",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(TicketActivity $ticketActivity): View
    {
        return $this->view(
            $this->mapper->reflect($ticketActivity)
        );
    }

    /**
     * @Get("/ticketing/tickets/activities", name="ticket_activities_collection_get", options={"method_prefix"=false})
     * @ViewHandler()
     * @Permission("view")
     * @QueryParam(
     *     name="createdDateFrom",
     *     requirements="\d{4}-\d{2}-\d{2}",
     *     strict=true,
     *     nullable=true,
     *     description="limit collection starting on date (including)"
     * )
     * @QueryParam(
     *     name="createdDateTo",
     *     requirements="\d{4}-\d{2}-\d{2}",
     *     strict=true,
     *     nullable=true,
     *     description="limit collection ending on date (including)"
     * )
     * @QueryParam(
     *     name="userId",
     *     requirements="\d+",
     *     strict=true,
     *     nullable=true,
     *     description="user ID"
     * )
     * @QueryParam(
     *     name="ticketId",
     *     requirements="\d+",
     *     strict=true,
     *     nullable=true,
     *     description="ticket ID"
     * )
     */
    public function getCollectionAction(ParamFetcherInterface $paramFetcher): View
    {
        $user = null;
        if ($userId = $paramFetcher->get('userId')) {
            $user = $this->em->getRepository(User::class)->findOneBy(
                [
                    'id' => $userId,
                    'role' => User::ADMIN_ROLES,
                ]
            );
            if (! $user) {
                throw new NotFoundHttpException('User object not found.');
            }
        }

        $ticket = null;
        if ($ticketId = $paramFetcher->get('ticketId')) {
            $ticket = $this->em->find(Ticket::class, $ticketId);
            if (! $ticket) {
                throw new NotFoundHttpException('Ticket object not found.');
            }
        }

        if ($startDate = $paramFetcher->get('createdDateFrom')) {
            try {
                $startDate = DateTimeFactory::createDate($startDate);
            } catch (\InvalidArgumentException $exception) {
                throw new HttpException(400, $exception->getMessage(), $exception);
            }
        }
        if ($endDate = $paramFetcher->get('createdDateTo')) {
            try {
                $endDate = DateTimeFactory::createDate($endDate);
                $endDate->setTime(23, 59, 59);
            } catch (\InvalidArgumentException $exception) {
                throw new HttpException(400, $exception->getMessage(), $exception);
            }
        }

        $ticketActivities = $this->dataProvider->getAll($ticket, $user, $startDate, $endDate);

        return $this->view(
            $this->mapper->reflectCollection($ticketActivities)
        );
    }
}
