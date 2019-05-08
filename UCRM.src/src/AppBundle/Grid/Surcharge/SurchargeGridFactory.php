<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Grid\Surcharge;

use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\Controller\SurchargeController;
use AppBundle\Facade\SurchargeFacade;
use AppBundle\Security\Permission;
use AppBundle\Util\Formatter;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SurchargeGridFactory
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
     * @var Formatter
     */
    private $formatter;

    /**
     * @var SurchargeFacade
     */
    private $surchargeFacade;

    public function __construct(
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        Formatter $formatter,
        SurchargeFacade $surchargeFacade
    ) {
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->formatter = $formatter;
        $this->surchargeFacade = $surchargeFacade;
    }

    public function create(): Grid
    {
        $qb = $this->surchargeFacade->getGridModel();

        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->setDefaultSort('s_name');
        $grid->addIdentifier('s_id', 's.id');
        $grid->setRowUrl('surcharge_show');

        $grid->attached();

        $grid->addTextColumn('s_name', 's.name', 'Name')
            ->setSortable();

        $grid->addTwigFilterColumn('s_price', 's.price', 'Price', 'localizedNumber')
            ->setSortable();

        $grid->addTwigFilterColumn('s_taxable', 's.taxable', 'Taxable', 'yesNo')
            ->setSortable();

        $grid->addEditActionButton('surcharge_edit', [], SurchargeController::class);

        $deleteMultiAction = $grid->addMultiAction(
            'delete',
            'Delete',
            function () use ($grid) {
                return $this->multiDeleteAction($grid);
            },
            [
                'button--danger',
            ],
            'Do you really want to delete these surcharges?',
            null,
            'ucrm-icon--trash'
        );
        $deleteMultiAction->confirmOkay = 'Delete forever';

        return $grid;
    }

    private function multiDeleteAction(Grid $grid): RedirectResponse
    {
        $this->gridHelper->denyAccessUnlessPermissionGranted(Permission::EDIT, SurchargeController::class);

        list($deleted, $failed) = $this->surchargeFacade->handleDeleteMultiple($grid->getDoMultiActionIds());

        if ($deleted > 0) {
            $this->gridHelper->addTranslatedFlash(
                'success',
                'Deleted %count% surcharges.',
                $deleted,
                [
                    '%count%' => $deleted,
                ]
            );
        }

        if ($failed > 0) {
            $this->gridHelper->addTranslatedFlash(
                'warning',
                '%count% surcharges could not be deleted.',
                $failed,
                [
                    '%count%' => $failed,
                ]
            );
        }

        return new RedirectResponse($grid->generateMultiActionReturnUrl());
    }
}
