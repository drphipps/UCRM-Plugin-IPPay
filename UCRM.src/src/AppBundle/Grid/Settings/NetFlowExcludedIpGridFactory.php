<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\Settings;

use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\Controller\SettingNetFlowController;
use AppBundle\Entity\NetflowExcludedIp;
use AppBundle\Facade\NetflowExcludedIpFacade;
use AppBundle\Security\Permission;
use Symfony\Component\HttpFoundation\RedirectResponse;

class NetFlowExcludedIpGridFactory
{
    /**
     * @var GridFactory
     */
    private $gridFactory;

    /**
     * @var GridHelper
     */
    private $gridHelper;

    /**
     * @var NetflowExcludedIpFacade
     */
    private $netflowExcludedIpFacade;

    public function __construct(
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        NetflowExcludedIpFacade $netflowExcludedIpFacade
    ) {
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->netflowExcludedIpFacade = $netflowExcludedIpFacade;
    }

    public function create(): Grid
    {
        $qb = $this->netflowExcludedIpFacade->getGridModel();
        $grid = $this->gridFactory->createGrid($qb, __CLASS__, 'nei_name');
        $grid->addIdentifier('nei_id', 'nei.id');
        $grid->attached();

        $grid->addCustomColumn(
            'nei_ipAddress',
            'IP address',
            function ($row) {
                /** @var NetflowExcludedIp $netflowExcludedIp */
                $netflowExcludedIp = $row[0];

                return long2ip($netflowExcludedIp->getIpAddress());
            }
        );

        $grid->addTextColumn('nei_name', 'nei.name', 'Name')->setSortable();

        $grid->addEditActionButton('setting_netflow_excluded_ip_edit', [], SettingNetFlowController::class, true);

        $grid->addDeleteActionButton('setting_netflow_excluded_ip_delete', [], SettingNetFlowController::class);

        $deleteMultiAction = $grid->addMultiAction(
            'delete',
            'Delete',
            function () use ($grid) {
                return $this->multiDeleteAction($grid);
            },
            [
                'button--danger',
            ],
            'Do you really want to delete these IP addresses?',
            null,
            'ucrm-icon--trash'
        );
        $deleteMultiAction->confirmOkay = 'Delete forever';

        return $grid;
    }

    private function multiDeleteAction(Grid $grid): RedirectResponse
    {
        $this->gridHelper->denyAccessUnlessPermissionGranted(Permission::EDIT, SettingNetFlowController::class);

        $deleted = $this->netflowExcludedIpFacade->handleDeleteMultiple($grid->getDoMultiActionIds());

        if ($deleted > 0) {
            $this->gridHelper->addTranslatedFlash(
                'success',
                'Deleted %count% excluded IPs.',
                $deleted,
                [
                    '%count%' => $deleted,
                ]
            );
        }

        return new RedirectResponse($grid->generateMultiActionReturnUrl());
    }
}
