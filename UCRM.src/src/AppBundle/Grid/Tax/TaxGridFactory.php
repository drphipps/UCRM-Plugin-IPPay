<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Grid\Tax;

use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\Controller\TaxController;
use AppBundle\Facade\TaxFacade;
use AppBundle\Security\Permission;

class TaxGridFactory
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
     * @var TaxFacade
     */
    private $taxFacade;

    public function __construct(GridFactory $gridFactory, GridHelper $gridHelper, TaxFacade $taxFacade)
    {
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->taxFacade = $taxFacade;
    }

    public function create(): Grid
    {
        $qb = $this->taxFacade->getGridModel();
        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->addIdentifier('t_id', 't.id');
        $grid->addIdentifier('t_selected', 't.selected');
        $grid->addIdentifier('t_rate', 't.rate');
        $grid->setDefaultSort('t_name');
        $grid->setRowUrl('tax_show');

        $grid->attached();

        $grid->addTextColumn('t_name', 't.name', 'Name')
            ->setSortable();

        $grid
            ->addCustomColumn(
                't_rate',
                'Rate',
                function ($row) {
                    return sprintf('%s %%', $row['t_rate']);
                }
            )
            ->setSortable();

        $defaultButton = $grid->addActionButton(
            'tax_default',
            [],
            TaxController::class,
            Permission::EDIT
        );
        $defaultButton->setTitle($this->gridHelper->trans('Default'));
        $defaultButton->setCssClasses(
            [
                'js-tax-default',
            ]
        );
        $defaultButton->addRenderCondition(
            function ($row) {
                return ! $row['t_selected'];
            }
        );

        $defaultSelectedButton = $grid->addActionButton(
            'tax_default',
            [],
            TaxController::class,
            Permission::EDIT
        );
        $defaultSelectedButton->setTitle($this->gridHelper->trans('Default'));
        $defaultSelectedButton->setCssClasses(
            [
                'js-tax-default',
                'button--primary-o',
            ]
        );
        $defaultSelectedButton->addRenderCondition(
            function ($row) {
                return $row['t_selected'];
            }
        );

        $grid->addEditActionButton('tax_edit', [], TaxController::class);
        $grid->addDeleteActionButton('tax_delete', [], TaxController::class);

        return $grid;
    }
}
