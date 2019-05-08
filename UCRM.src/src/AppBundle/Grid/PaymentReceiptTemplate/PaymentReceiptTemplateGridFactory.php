<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\PaymentReceiptTemplate;

use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\Controller\PaymentReceiptTemplateController;
use AppBundle\DataProvider\PaymentReceiptTemplateDataProvider;
use AppBundle\Security\Permission;
use AppBundle\Util\Formatter;

class PaymentReceiptTemplateGridFactory
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
     * @var PaymentReceiptTemplateDataProvider
     */
    private $paymentReceiptTemplateDataProvider;

    public function __construct(
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        PaymentReceiptTemplateDataProvider $paymentReceiptTemplateDataProvider
    ) {
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->paymentReceiptTemplateDataProvider = $paymentReceiptTemplateDataProvider;
    }

    public function create(): Grid
    {
        $qb = $this->paymentReceiptTemplateDataProvider->getGridModel();
        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->setRowUrl('payment_receipt_template_show');
        $grid->addIdentifier('prt_id', 'prt.id');
        $grid->addIdentifier('prt_official_name', 'prt.officialName');
        $grid->setDefaultSort('prt_name');

        $grid->attached();

        $grid->addTextColumn('prt_name', 'prt.name', 'Name')->setSortable();
        $grid
            ->addTwigFilterColumn(
                'prt_created_date',
                'prt.createdDate',
                'Created date',
                'localizedDate',
                [Formatter::DEFAULT, Formatter::NONE]
            )
            ->setSortable();

        $grid
            ->addEditActionButton('payment_receipt_template_edit', [], PaymentReceiptTemplateController::class)
            ->addRenderCondition(
                function ($row) {
                    return ! $row['prt_official_name'];
                }
            );

        $cloneButton = $grid->addActionButton(
            'payment_receipt_template_clone',
            [],
            PaymentReceiptTemplateController::class,
            Permission::EDIT
        );
        $cloneButton->setIcon('ucrm-icon--plus-recurring');
        $cloneButton->setData(
            [
                'tooltip' => $this->gridHelper->trans('Clone'),
            ]
        );

        $grid
            ->addDeleteActionButton('payment_receipt_template_delete', [], PaymentReceiptTemplateController::class)
            ->addRenderCondition(
                function ($row) {
                    return ! $row['prt_official_name'];
                }
            );

        return $grid;
    }
}
