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
use AppBundle\Controller\ClientZonePageController;
use AppBundle\Facade\ClientZonePageFacade;
use AppBundle\Security\Permission;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ClientZonePageGridFactory
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
     * @var ClientZonePageFacade
     */
    private $pageFacade;

    public function __construct(GridFactory $gridFactory, GridHelper $gridHelper, ClientZonePageFacade $pageFacade)
    {
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->pageFacade = $pageFacade;
    }

    public function create(): Grid
    {
        $qb = $this->pageFacade->getGridModel();
        $grid = $this->gridFactory->createGrid($qb, __CLASS__, 'page_name');
        $grid->addIdentifier('page_id', 'page.id');
        $grid->attached();

        $grid->setRowUrl('client_zone_page_show');

        $grid->addTextColumn('page_name', 'page.name', 'Name');

        $grid->addTwigFilterColumn('page_public', 'page.public', 'Public', 'yesNo');

        $grid->setDefaultSort('page.position');

        $upButton = $grid->addActionButton(
            'client_zone_page_position_up',
            [],
            ClientZonePageController::class,
            'edit'
        );
        $upButton->setIcon('ucrm-icon--arrow-up');
        $upButton->setData(
            [
                'tooltip' => $this->gridHelper->trans('Move the page up in the Client zone main menu'),
            ]
        );

        $downButton = $grid->addActionButton(
            'client_zone_page_position_down',
            [],
            ClientZonePageController::class,
            'edit'
        );
        $downButton->setIcon('ucrm-icon--arrow-down');
        $downButton->setData(
            [
                'tooltip' => $this->gridHelper->trans('Move the page down in the Client zone main menu'),
            ]
        );

        $grid->addEditActionButton('client_zone_page_edit', [], ClientZonePageController::class);

        $grid->addDeleteActionButton('client_zone_page_delete', [], ClientZonePageController::class);

        $deleteMultiAction = $grid->addMultiAction(
            'delete',
            'Delete',
            function () use ($grid) {
                return $this->multiDeleteAction($grid);
            },
            [
                'button--danger',
            ],
            'Do you really want to delete these items?',
            null,
            'ucrm-icon--trash'
        );
        $deleteMultiAction->confirmOkay = 'Delete forever';

        return $grid;
    }

    private function multiDeleteAction(Grid $grid): RedirectResponse
    {
        $this->gridHelper->denyAccessUnlessPermissionGranted(Permission::EDIT, ClientZonePageController::class);

        $deleted = $this->pageFacade->handleDeleteMultiple($grid->getDoMultiActionIds());

        if ($deleted > 0) {
            $this->gridHelper->addTranslatedFlash(
                'success',
                'Deleted %count% items.',
                $deleted,
                [
                    '%count%' => $deleted,
                ]
            );
        }

        return new RedirectResponse($grid->generateMultiActionReturnUrl());
    }
}
