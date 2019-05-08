<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Grid\OrganizationBankAccount;

use AppBundle\Component\Grid\Column\BaseColumn;
use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Controller\OrganizationBankAccountController;
use AppBundle\Entity\OrganizationBankAccount;
use AppBundle\Facade\OrganizationBankAccountFacade;

class OrganizationBankAccountGridFactory
{
    /**
     * @var GridFactory
     */
    private $gridFactory;

    /**
     * @var OrganizationBankAccountFacade
     */
    private $organizationBankAccountFacade;

    public function __construct(
        GridFactory $gridFactory,
        OrganizationBankAccountFacade $organizationBankAccountFacade
    ) {
        $this->gridFactory = $gridFactory;
        $this->organizationBankAccountFacade = $organizationBankAccountFacade;
    }

    public function create(): Grid
    {
        $qb = $this->organizationBankAccountFacade->getGridModel();
        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->addIdentifier('oba_id', 'oba.id');
        $grid->setDefaultSort('oba_name');
        $grid->setRowUrl('organization_bank_account_show');

        $grid->attached();

        $grid->addTextColumn('oba_name', 'oba.name', 'Name')
            ->setSortable();
        $grid->addCustomColumn(
            'oba_number',
            'Account number',
            function ($row) {
                /** @var OrganizationBankAccount $bankAccount */
                $bankAccount = $row[0];

                return $bankAccount->getFieldsForView() ?: BaseColumn::EMPTY_COLUMN;
            }
        );

        $grid->addEditActionButton('organization_bank_account_edit', [], OrganizationBankAccountController::class);
        $grid->addDeleteActionButton('organization_bank_account_delete', [], OrganizationBankAccountController::class);

        return $grid;
    }
}
