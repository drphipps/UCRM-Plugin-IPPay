<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Grid\DeviceLog\DeviceLogGridFactory;
use AppBundle\Security\Permission;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/logs/device")
 */
class DeviceLogController extends BaseController
{
    /**
     * @Route("", name="device_log_index")
     * @Method("GET")
     * @Permission("view")
     * @Searchable(heading="Device log", path="System -> Logs -> Device log")
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(DeviceLogGridFactory::class)->create();
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'device_log/index.html.twig',
            [
                'grid' => $grid,
            ]
        );
    }
}
