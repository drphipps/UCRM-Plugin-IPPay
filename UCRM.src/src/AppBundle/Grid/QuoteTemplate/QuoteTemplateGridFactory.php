<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\QuoteTemplate;

use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\Controller\QuoteTemplateController;
use AppBundle\DataProvider\QuoteTemplateDataProvider;
use AppBundle\Security\Permission;
use AppBundle\Util\Formatter;

class QuoteTemplateGridFactory
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
     * @var QuoteTemplateDataProvider
     */
    private $quoteTemplateDataProvider;

    public function __construct(
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        QuoteTemplateDataProvider $quoteTemplateDataProvider
    ) {
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->quoteTemplateDataProvider = $quoteTemplateDataProvider;
    }

    public function create(): Grid
    {
        $qb = $this->quoteTemplateDataProvider->getGridModel();
        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->setRowUrl('quote_template_show');
        $grid->addIdentifier('qt_id', 'qt.id');
        $grid->addIdentifier('qt_official_name', 'qt.officialName');
        $grid->setDefaultSort('qt_name');

        $grid->attached();

        $grid->addTextColumn('qt_name', 'qt.name', 'Name')->setSortable();
        $grid
            ->addTwigFilterColumn(
                'qt_created_date',
                'qt.createdDate',
                'Created date',
                'localizedDate',
                [Formatter::DEFAULT, Formatter::NONE]
            )
            ->setSortable();

        $grid
            ->addEditActionButton('quote_template_edit', [], QuoteTemplateController::class)
            ->addRenderCondition(
                function ($row) {
                    return ! $row['qt_official_name'];
                }
            );

        $cloneButton = $grid->addActionButton(
            'quote_template_clone',
            [],
            QuoteTemplateController::class,
            Permission::EDIT
        );
        $cloneButton->setIcon('ucrm-icon--plus-recurring');
        $cloneButton->setData(
            [
                'tooltip' => $this->gridHelper->trans('Clone'),
            ]
        );

        $grid
            ->addDeleteActionButton('quote_template_delete', [], QuoteTemplateController::class)
            ->addRenderCondition(
                function ($row) {
                    return ! $row['qt_official_name'];
                }
            );

        return $grid;
    }
}
