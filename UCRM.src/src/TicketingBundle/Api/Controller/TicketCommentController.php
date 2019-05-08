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
use AppBundle\Entity\User;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Util\DateTimeFactory;
use AppBundle\Util\Helpers;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use TicketingBundle\Api\Map\TicketCommentMap;
use TicketingBundle\Api\Mapper\TicketCommentMapper;
use TicketingBundle\Controller\TicketController as AppTicketController;
use TicketingBundle\DataProvider\TicketActivityDataProvider;
use TicketingBundle\Entity\Ticket;
use TicketingBundle\Entity\TicketComment;
use TicketingBundle\Request\TicketCommentsRequest;
use TicketingBundle\Service\Facade\TicketCommentFacade;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(AppTicketController::class)
 */
class TicketCommentController extends BaseController implements AppKeyAuthenticatedInterface
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
     * @var TicketCommentMapper
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
        TicketCommentMapper $mapper,
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
     *     "/ticketing/tickets/comments/{id}",
     *     name="ticket_comment_get",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(TicketComment $ticketComment): View
    {
        return $this->view(
            $this->mapper->reflect($ticketComment)
        );
    }

    /**
     * @Get("/ticketing/tickets/comments", name="ticket_comments_collection_get", options={"method_prefix"=false})
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

        $ticketCommentsRequest = new TicketCommentsRequest();
        $ticketCommentsRequest->ticket = $ticket;
        $ticketCommentsRequest->user = $user;
        $ticketCommentsRequest->startDate = $startDate;
        $ticketCommentsRequest->endDate = $endDate;
        $ticketComments = $this->dataProvider->getAllTicketComments($ticketCommentsRequest);

        return $this->view(
            $this->mapper->reflectCollection($ticketComments)
        );
    }

    /**
     * @Post("/ticketing/tickets/comments", name="ticket_comment_add", options={"method_prefix"=false})
     * @ParamConverter("ticketCommentMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function postAction(TicketCommentMap $ticketCommentMap, string $version): View
    {
        if (Helpers::isDemo()) {
            $ticketCommentMap->attachments = [];
        }
        $ticketComment = new TicketComment();
        $this->mapper->map($ticketCommentMap, $ticketComment);
        $this->validator->validate($ticketComment, $this->mapper->getFieldsDifference());
        $this->facade->handleNewFromAPI($ticketComment, $ticketCommentMap->attachments);

        return $this->routeRedirectViewWithData(
            $this->mapper->reflect($ticketComment),
            'api_ticket_comment_get',
            [
                'version' => $version,
                'id' => $ticketComment->getId(),
            ]
        );
    }
}
