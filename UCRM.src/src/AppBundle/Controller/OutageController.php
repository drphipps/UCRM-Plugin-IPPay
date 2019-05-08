<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Ping\DeviceOutageProvider;
use AppBundle\Entity\Site;
use AppBundle\Grid\Outage\NetworkDeviceOutageGridFactory;
use AppBundle\Grid\Outage\ServiceDeviceOutageGridFactory;
use AppBundle\Security\Permission;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/outages")
 */
class OutageController extends BaseController
{
    const NETWORK_DEVICES = 'network-devices';
    const SERVICE_DEVICES = 'service-devices';
    const EXCLUDE_ENDED_FILTER = [
        1 => 'Show ongoing only',
        '' => 'Show all',
    ];

    /**
     * @Route(
     *     "/{filterType}",
     *     name="outage_index",
     *     defaults={"filterType" = "network-devices"},
     *     requirements={"filterType": "network-devices|service-devices"}
     * )
     * @Method("GET")
     * @Permission("view")
     */
    public function indexAction(Request $request, string $filterType): Response
    {
        if ($filterType === self::NETWORK_DEVICES) {
            $grid = $this->get(NetworkDeviceOutageGridFactory::class)->create();
        } else {
            $grid = $this->get(ServiceDeviceOutageGridFactory::class)->create();
        }
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'outage/index.html.twig',
            [
                'grid' => $grid,
                'filterType' => $filterType,
                'networkDevices' => self::NETWORK_DEVICES,
                'serviceDevices' => self::SERVICE_DEVICES,
            ]
        );
    }

    /**
     * @Route(
     *     "/device-filter-options/{id}",
     *     name="outage_device_filter_options",
     *     defaults={"id":null},
     *     options={"expose":true}
     * )
     * @Method("GET")
     * @Permission("view")
     */
    public function getDeviceFilterOptionsAction(Site $site = null): Response
    {
        return new Response($this->get(DeviceOutageProvider::class)->getDevicesFormHtml($site));
    }
}
