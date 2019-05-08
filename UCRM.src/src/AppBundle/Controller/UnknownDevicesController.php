<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Grid\IpAccounting\IpAccountingGridFactory;
use AppBundle\Grid\ServiceDevice\UnknownServiceDeviceGridFactory;
use AppBundle\Security\Permission;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/unknown-devices")
 */
class UnknownDevicesController extends BaseController
{
    /**
     * @Route("", name="unknown_devices_index")
     * @Method("GET")
     * @Permission("view")
     */
    public function indexAction(Request $request): Response
    {
        $ipAccountingGrid = $this->get(IpAccountingGridFactory::class)->create();
        if ($parameters = $ipAccountingGrid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        $serviceDevicesGrid = $this->get(UnknownServiceDeviceGridFactory::class)->create();
        if ($parameters = $serviceDevicesGrid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'unknown_devices/index.html.twig',
            [
                'ipAccountingGrid' => $ipAccountingGrid,
                'serviceDevicesGrid' => $serviceDevicesGrid,
            ]
        );
    }
}
