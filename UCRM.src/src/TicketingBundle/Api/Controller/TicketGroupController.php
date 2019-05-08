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
use TicketingBundle\Api\Map\TicketGroupMap;
use TicketingBundle\Api\Mapper\TicketGroupMapper;
use TicketingBundle\Controller\TicketController as AppTicketController;
use TicketingBundle\DataProvider\TicketGroupDataProvider;
use TicketingBundle\Entity\TicketGroup;
use TicketingBundle\Service\Facade\TicketGroupFacade;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(AppTicketController::class)
 */
class TicketGroupController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var TicketGroupDataProvider
     */
    private $dataProvider;

    /**
     * @var TicketGroupFacade
     */
    private $facade;

    /**
     * @var TicketGroupMapper
     */
    private $mapper;

    /**
     * @var Validator
     */
    private $validator;

    public function __construct(
        TicketGroupDataProvider $dataProvider,
        TicketGroupFacade $facade,
        TicketGroupMapper $mapper,
        Validator $validator
    ) {
        $this->dataProvider = $dataProvider;
        $this->facade = $facade;
        $this->mapper = $mapper;
        $this->validator = $validator;
    }

    /**
     * @Delete("/ticketing/ticket-groups/{id}", name="ticket_group_delete", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ViewHandler()
     * @Permission("edit")
     */
    public function deleteAction(TicketGroup $ticketGroup): View
    {
        $this->facade->handleDelete($ticketGroup);

        return $this->view(null, 200);
    }

    /**
     * @Get("/ticketing/ticket-groups/{id}", name="ticket_group_get", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(TicketGroup $ticketGroup): View
    {
        return $this->view(
            $this->mapper->reflect($ticketGroup)
        );
    }

    /**
     * @Get("/ticketing/ticket-groups", name="ticket_group_collection_get", options={"method_prefix"=false})
     * @ViewHandler()
     * @Permission("view")
     */
    public function getCollectionAction(): View
    {
        return $this->view(
            $this->mapper->reflectCollection($this->dataProvider->findAllTicketGroups())
        );
    }

    /**
     * @Patch("/ticketing/ticket-groups/{id}", name="ticket_group_edit", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ParamConverter("ticketGroupMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function patchAction(TicketGroup $ticketGroup, TicketGroupMap $ticketGroupMap): View
    {
        $this->mapper->map($ticketGroupMap, $ticketGroup);
        $this->validator->validate($ticketGroup, $this->mapper->getFieldsDifference());
        $this->facade->handleUpdate($ticketGroup);

        return $this->view(
            $this->mapper->reflect($ticketGroup)
        );
    }

    /**
     * @Post("/ticketing/ticket-groups", name="ticket_group_add", options={"method_prefix"=false})
     * @ParamConverter("ticketGroupMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function postAction(TicketGroupMap $ticketGroupMap, string $version): View
    {
        $ticketGroup = new TicketGroup();
        $this->mapper->map($ticketGroupMap, $ticketGroup);
        $this->validator->validate($ticketGroup, $this->mapper->getFieldsDifference());
        $this->facade->handleCreate($ticketGroup);

        return $this->routeRedirectViewWithData(
            $this->mapper->reflect($ticketGroup),
            'api_ticket_group_get',
            [
                'version' => $version,
                'id' => $ticketGroup->getId(),
            ]
        );
    }
}
