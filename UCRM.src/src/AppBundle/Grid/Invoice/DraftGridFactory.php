<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\Invoice;

use AppBundle\Component\Elastic\Search;
use AppBundle\Component\Grid\Column\BaseColumn;
use AppBundle\Component\Grid\Grid;
use AppBundle\Controller\InvoiceController;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Util\Formatter;
use Doctrine\ORM\QueryBuilder;

class DraftGridFactory extends BaseInvoiceGridFactory
{
    public function create(): Grid
    {
        $qb = $this->invoiceDataProvider->getGridModel()
            ->andWhere('i.invoiceStatus = :invoiceStatusDraft')
            ->setParameter('invoiceStatusDraft', Invoice::DRAFT);

        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->setRowUrl('billing_invoice_show');
        $grid->addIdentifier('i_id', 'i.id');
        $grid->addIdentifier('client_id', 'c.id');
        $grid->addIdentifier('i_due_date', 'i.dueDate');
        $grid->setDefaultSort('i_created_date', Grid::DESC);

        $grid->attached();

        $grid
            ->addCustomColumn(
                'c_fullname',
                'Client',
                function ($row) {
                    return $row['c_fullname'] ?: BaseColumn::EMPTY_COLUMN;
                }
            )
            ->setSortable();

        $grid
            ->addCustomColumn(
                'i_total',
                'Total',
                function ($row) {
                    /** @var Invoice $invoice */
                    $invoice = $row[0];

                    return $this->formatter->formatCurrency(
                        $row['i_total'],
                        $invoice->getCurrency()->getCode(),
                        $invoice->getOrganization()->getLocale()
                    );
                }
            )
            ->setSortable()
            ->setIsGrouped()
            ->setAlignRight();

        $grid
            ->addCustomColumn(
                'i_to_pay',
                'Amount due',
                function ($row) {
                    /** @var Invoice $invoice */
                    $invoice = $row[0];

                    return $this->formatter->formatCurrency(
                        $row['i_to_pay'],
                        $invoice->getCurrency()->getCode(),
                        $invoice->getOrganization()->getLocale()
                    );
                }
            )
            ->setSortable()
            ->setAlignRight();

        $grid
            ->addTwigFilterColumn(
                'i_created_date',
                'i.createdDate',
                'Created date',
                'localizedDate',
                [Formatter::DEFAULT, Formatter::NONE]
            )
            ->setSortable()
            ->setIsGrouped()
            ->setOrderByCallback(
                function (QueryBuilder $model, string $direction) {
                    $model->orderBy('i.createdDate', $direction);
                    $model->addOrderBy('i.id', $direction);
                }
            );

        $grid->addRawCustomColumn('i_due_date', 'Due date', [$this, 'renderDueDate'])
            ->setSortable();

        $grid->addEditActionButton('client_invoice_edit', [], InvoiceController::class);

        $tooltip = sprintf(
            '%s%s',
            $this->gridHelper->trans('Search by client name or email'),
            html_entity_decode('&hellip;')
        );
        $grid->addElasticFilter('search', 'i.id', $tooltip, Search::TYPE_INVOICE, $this->gridHelper->trans('Search'));

        $grid->addDateFilter('created_date', 'i.createdDate', 'Created date', true);

        $organizations = $this->organizationFacade->findAllForm();
        if (count($organizations) > 1) {
            $grid->addCustomColumn(
                'o_name',
                'Organization',
                function ($row) {
                    return $row['o_name'] ?: BaseColumn::EMPTY_COLUMN;
                }
            );

            $grid->addSelectFilter('organization', 'o.id', 'Organization', $organizations);
        }

        $grid->addMultiAction(
            'approve',
            'Approve',
            function () use ($grid) {
                return $this->multiApproveAction($grid);
            },
            [
                'button--primary',
            ],
            null,
            null,
            'ucrm-icon--check-narrow'
        );

        $deleteMultiAction = $grid->addMultiAction(
            'delete',
            'Delete',
            function () use ($grid) {
                return $this->multiDeleteAction($grid);
            },
            [
                'button--danger',
            ],
            'Do you really want to delete these drafts?',
            null,
            'ucrm-icon--trash'
        );
        $deleteMultiAction->confirmOkay = 'Delete forever';

        return $grid;
    }
}
