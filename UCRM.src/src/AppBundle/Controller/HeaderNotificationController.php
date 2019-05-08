<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Entity\HeaderNotificationStatus;
use AppBundle\Facade\HeaderNotificationFacade;
use AppBundle\Grid\HeaderNotification\HeaderNotificationGridFactory;
use AppBundle\Security\Permission;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/notifications")
 */
class HeaderNotificationController extends BaseController
{
    /**
     * @Route("", name="header_notifications_index")
     * @Method("GET")
     * @Permission("guest")
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(HeaderNotificationGridFactory::class)->create($this->getUser());
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'header_notification/index.html.twig',
            [
                'grid' => $grid,
            ]
        );
    }

    /**
     * @Route("/list", name="header_notifications_list", options={"expose"=true})
     * @Method("GET")
     * @Permission("guest")
     */
    public function listAction(): Response
    {
        $notificationStatuses = $this->em->getRepository(HeaderNotificationStatus::class)->getByUser(
            $this->getUser(),
            10
        );

        return $this->render(
            'header_notification/list.html.twig',
            [
                'notificationStatuses' => $notificationStatuses,
            ]
        );
    }

    /**
     * @Route(
     *     "/open/{id}",
     *     name="header_notifications_open",
     *     requirements={"id": "%uuid_regex%"}
     * )
     * @Method("GET")
     * @Permission("guest")
     */
    public function openAction(HeaderNotificationStatus $notificationStatus): Response
    {
        if ($notificationStatus->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $link = $notificationStatus->getHeaderNotification()->getLink();
        if (! $link) {
            throw $this->createNotFoundException();
        }

        $this->get(HeaderNotificationFacade::class)->markAsRead($notificationStatus);

        return $this->redirect($link);
    }

    /**
     * @Route("/mark-all-as-read", name="header_notifications_mark_all_as_read", options={"expose"=true})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("guest")
     */
    public function markAllAsReadAction(): Response
    {
        $this->get(HeaderNotificationFacade::class)->markAllAsRead($this->getUser());

        return new Response();
    }

    /**
     * @Route(
     *     "/mark-as-read/{id}",
     *     name="header_notifications_mark_as_read",
     *     options={"expose"=true},
     *     requirements={"id": "%uuid_regex%"}
     * )
     * @Method("GET")
     * @CsrfToken()
     * @Permission("guest")
     */
    public function markAsReadAction(HeaderNotificationStatus $notificationStatus): Response
    {
        if ($notificationStatus->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $this->get(HeaderNotificationFacade::class)->markAsRead($notificationStatus);

        return new Response();
    }
}
