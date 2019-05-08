<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\ProformaInvoiceTemplate;

use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\Controller\ProformaInvoiceTemplateController;
use AppBundle\DataProvider\ProformaInvoiceTemplateDataProvider;
use AppBundle\Security\Permission;
use AppBundle\Util\Formatter;

class ProformaInvoiceTemplateGridFactory
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
     * @var ProformaInvoiceTemplateDataProvider
     */
    private $proformaInvoiceTemplateDataProvider;

    public function __construct(
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        ProformaInvoiceTemplateDataProvider $proformaInvoiceTemplateDataProvider
    ) {
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->proformaInvoiceTemplateDataProvider = $proformaInvoiceTemplateDataProvider;
    }

    public function create(): Grid
    {
        $qb = $this->proformaInvoiceTemplateDataProvider->getGridModel();
        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->setRowUrl('proforma_invoice_template_show');
        $grid->addIdentifier('it_id', 'it.id');
        $grid->addIdentifier('it_official_name', 'it.officialName');
        $grid->setDefaultSort('it_name');

        $grid->attached();

        $grid->addTextColumn('it_name', 'it.name', 'Name')->setSortable();
        $grid
            ->addTwigFilterColumn(
                'it_created_date',
                'it.createdDate',
                'Created date',
                'localizedDate',
                [Formatter::DEFAULT, Formatter::NONE]
            )
            ->setSortable();

        $grid
            ->addEditActionButton('proforma_invoice_template_edit', [], ProformaInvoiceTemplateController::class)
            ->addRenderCondition(
                function ($row) {
                    return ! $row['it_official_name'];
                }
            );

        $cloneButton = $grid->addActionButton(
            'proforma_invoice_template_clone',
            [],
            ProformaInvoiceTemplateController::class,
            Permission::EDIT
        );
        $cloneButton->setIcon('ucrm-icon--plus-recurring');
        $cloneButton->setData(
            [
                'tooltip' => $this->gridHelper->trans('Clone'),
            ]
        );

        $grid
            ->addDeleteActionButton('proforma_invoice_template_delete', [], ProformaInvoiceTemplateController::class)
            ->addRenderCondition(
                function ($row) {
                    return ! $row['it_official_name'];
                }
            );

        return $grid;
    }
}
