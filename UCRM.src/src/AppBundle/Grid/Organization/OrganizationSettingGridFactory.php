<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\Organization;

use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\Entity\Organization;
use AppBundle\Facade\OrganizationFacade;
use Nette\Utils\Html;

class OrganizationSettingGridFactory
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
     * @var OrganizationFacade
     */
    private $organizationFacade;

    public function __construct(
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        OrganizationFacade $organizationFacade
    ) {
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->organizationFacade = $organizationFacade;
    }

    public function create(): Grid
    {
        $qb = $this->organizationFacade->getGridModel();

        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->setDefaultSort('o_name');
        $grid->addIdentifier('o_id', 'o.id');
        $grid->setRowUrl('organization_show');

        $grid->attached();

        $grid->addTextColumn('o_name', 'o.name', 'Name')
            ->setSortable();
        $grid->addTextColumn('o_registrationNumber', 'o.registrationNumber', 'Registration number')
            ->setSortable();
        $grid->addTextColumn('o_taxId', 'o.taxId', 'Tax ID')
            ->setSortable();
        $grid->addTextColumn('o_invoiceMaturityDays', 'o.invoiceMaturityDays', 'Invoice maturity days')
            ->setSortable();
        $grid
            ->addCustomColumn(
                'c_currency',
                'Currency',
                function ($row) {
                    /** @var Organization $organization */
                    $organization = $row[0];

                    return $organization->getCurrency()->getCurrencyLabel();
                }
            )
            ->setSortable();

        $grid
            ->addRawCustomColumn(
                'o_invoiceTemplate',
                'Invoice template',
                function ($row) {
                    /** @var Organization $organization */
                    $organization = $row[0];
                    $invoiceTemplate = $organization->getInvoiceTemplate();

                    $span = Html::el('span');
                    $span->addText($invoiceTemplate->getName());

                    if (! $invoiceTemplate->getIsValid()) {
                        $invalid = Html::el(
                            'span',
                            [
                                'class' => 'icon ucrm-icon--danger-fill danger',
                                'data-tooltip' => $this->gridHelper->trans(
                                    'Invoice template contains errors and can\'t be safely used.'
                                ),
                            ]
                        );
                        $span->addHtml(' ' . $invalid);
                    }

                    return (string) $span;
                }
            )
            ->setSortable();

        $grid
            ->addRawCustomColumn(
                'o_quoteTemplate',
                'Quote template',
                function ($row) {
                    /** @var Organization $organization */
                    $organization = $row[0];
                    $quoteTemplate = $organization->getQuoteTemplate();

                    $span = Html::el('span');
                    $span->addText($quoteTemplate->getName());

                    if (! $quoteTemplate->getIsValid()) {
                        $invalid = Html::el(
                            'span',
                            [
                                'class' => 'icon ucrm-icon--danger-fill danger',
                                'data-tooltip' => $this->gridHelper->trans(
                                    'Quote template contains errors and can\'t be safely used.'
                                ),
                            ]
                        );
                        $span->addHtml(' ' . $invalid);
                    }

                    return (string) $span;
                }
            )
            ->setSortable();

        $grid
            ->addRawCustomColumn(
                'o_receiptTemplate',
                'Receipt template',
                function ($row) {
                    /** @var Organization $organization */
                    $organization = $row[0];
                    $receiptTemplate = $organization->getPaymentReceiptTemplate();

                    $span = Html::el('span');
                    $span->addText($receiptTemplate->getName());

                    if (! $receiptTemplate->getIsValid()) {
                        $invalid = Html::el(
                            'span',
                            [
                                'class' => 'icon ucrm-icon--danger-fill danger',
                                'data-tooltip' => $this->gridHelper->trans(
                                    'Receipt template contains errors and can\'t be safely used.'
                                ),
                            ]
                        );
                        $span->addHtml(' ' . $invalid);
                    }

                    return (string) $span;
                }
            )
            ->setSortable();

        $grid->addEditActionButton('organization_edit');

        return $grid;
    }
}
