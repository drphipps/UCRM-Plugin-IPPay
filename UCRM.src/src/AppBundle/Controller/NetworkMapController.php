<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Controller;

use AppBundle\Component\Map\NetworkMapProvider;
use AppBundle\Component\Map\Request\NetworkMapRequest;
use AppBundle\Form\NetworkMapFilterType;
use AppBundle\Security\Permission;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/network-map")
 */
class NetworkMapController extends BaseController
{
    /**
     * @Route("", name="network_map_index")
     * @Method("GET")
     * @Permission("view")
     */
    public function indexAction(Request $request): Response
    {
        $networkMapRequest = new NetworkMapRequest();
        $form = $this->createForm(
            NetworkMapFilterType::class,
            $networkMapRequest,
            [
                'method' => 'GET',
                'csrf_protection' => false,
            ]
        );
        $form->handleRequest($request);

        return $this->render(
            'network_map/index.html.twig',
            [
                'map' => $this->get(NetworkMapProvider::class)->getData($networkMapRequest),
                'form' => $form->createView(),
                'networkMapRequest' => $networkMapRequest,
            ]
        );
    }
}
