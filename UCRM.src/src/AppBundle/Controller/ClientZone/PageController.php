<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Controller\ClientZone;

use AppBundle\Entity\ClientZonePage;
use AppBundle\Security\Permission;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/client-zone/page")
 */
class PageController extends BaseController
{
    /**
     * @Route("/{id}", name="client_zone_page", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("guest")
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function indexAction(ClientZonePage $clientZonePage): Response
    {
        if (! $clientZonePage->isPublic()) {
            throw $this->createNotFoundException();
        }

        return $this->render(
            'client_zone/page/show.html.twig',
            [
                'page' => $clientZonePage,
            ]
        );
    }
}
