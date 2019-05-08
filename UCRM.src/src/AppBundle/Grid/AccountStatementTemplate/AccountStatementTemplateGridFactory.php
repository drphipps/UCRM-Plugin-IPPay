<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\AccountStatementTemplate;

use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\Controller\AccountStatementTemplateController;
use AppBundle\DataProvider\AccountStatementTemplateDataProvider;
use AppBundle\Security\Permission;
use AppBundle\Util\Formatter;

class AccountStatementTemplateGridFactory
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
     * @var AccountStatementTemplateDataProvider
     */
    private $accountStatementTemplateDataProvider;

    public function __construct(
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        AccountStatementTemplateDataProvider $accountStatementTemplateDataProvider
    ) {
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->accountStatementTemplateDataProvider = $accountStatementTemplateDataProvider;
    }

    public function create(): Grid
    {
        $qb = $this->accountStatementTemplateDataProvider->getGridModel();
        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->setRowUrl('account_statement_template_show');
        $grid->addIdentifier('ast_id', 'ast.id');
        $grid->addIdentifier('ast_official_name', 'ast.officialName');
        $grid->setDefaultSort('ast_name');

        $grid->attached();

        $grid->addTextColumn('ast_name', 'ast.name', 'Name')->setSortable();
        $grid
            ->addTwigFilterColumn(
                'ast_created_date',
                'ast.createdDate',
                'Created date',
                'localizedDate',
                [Formatter::DEFAULT, Formatter::NONE]
            )
            ->setSortable();

        $grid
            ->addEditActionButton('account_statement_template_edit', [], AccountStatementTemplateController::class)
            ->addRenderCondition(
                function ($row) {
                    return ! $row['ast_official_name'];
                }
            );

        $cloneButton = $grid->addActionButton(
            'account_statement_template_clone',
            [],
            AccountStatementTemplateController::class,
            Permission::EDIT
        );
        $cloneButton->setIcon('ucrm-icon--plus-recurring');
        $cloneButton->setData(
            [
                'tooltip' => $this->gridHelper->trans('Clone'),
            ]
        );

        $grid
            ->addDeleteActionButton('account_statement_template_delete', [], AccountStatementTemplateController::class)
            ->addRenderCondition(
                function ($row) {
                    return ! $row['ast_official_name'];
                }
            );

        return $grid;
    }
}
