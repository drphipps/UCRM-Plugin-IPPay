<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Grid\Organization\OrganizationSettingGridFactory;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/billing/organizations-settings")
 * @PermissionControllerName(OrganizationController::class)
 */
class OrganizationSettingController extends BaseController
{
    /**
     * @Route("", name="organization_setting_index")
     * @Method("GET")
     * @Permission("view")
     * @Searchable(heading="Organizations settings", path="System -> Billing -> Organizations settings")
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(OrganizationSettingGridFactory::class)->create();
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'organization/setting.html.twig',
            [
                'grid' => $grid,
            ]
        );
    }
}
