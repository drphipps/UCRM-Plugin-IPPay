<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Grid\Invoice;

use AppBundle\Component\Grid\Component\MultiActionGroup;
use AppBundle\Component\Grid\Grid;
use AppBundle\Controller\InvoiceController;
use AppBundle\Entity\Client;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Util\Formatter;
use Doctrine\ORM\QueryBuilder;

class ClientInvoiceGridFactory extends BaseInvoiceGridFactory
{
    public function createInvoiceGrid(Client $client): Grid
    {
        return $this->create($client);
    }

    public function createProformaGrid(Client $client): Grid
    {
        return $this->create($client, true);
    }

    private function create(Client $client, bool $proformaOnly = false): Grid
    {
        $qb = $this->invoiceDataProvider->getGridModel($client);
        $qb
            ->andWhere('i.isProforma = :isProforma')
            ->setParameter('isProforma', $proformaOnly);

        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->setRowUrl('client_invoice_show');
        $grid->addIdentifier('i_id', 'i.id');
        $grid->addIdentifier('i_due_date', 'i.dueDate');
        $grid->setDefaultSort('i_created_date', Grid::DESC);
        $grid->addRouterUrlParam('id', $client->getId());

        $grid->attached();

        $grid
            ->addRawCustomColumn(
                'i_invoice_number',
                'Invoice number',
                function ($row) {
                    /** @var Invoice $invoice */
                    $invoice = $row[0];

                    return sprintf(
                        '%s%s%s',
                        htmlspecialchars($row['i_invoice_number'] ?? '', ENT_QUOTES),
                        $this->badgeFactory->createInvoiceStatusBadge($row['i_invoice_status'], true),
                        $this->badgeFactory->createInvoiceUncollectibleBadge($invoice)
                    );
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

        $grid
            ->addEditActionButton('client_invoice_edit', [], InvoiceController::class)
            ->addRenderCondition(
                function ($row) {
                    /** @var Invoice $invoice */
                    $invoice = $row[0];

                    return $invoice->isEditable();
                }
            );

        $statusFilter = Invoice::STATUS_REPLACE_STRING;
        unset($statusFilter[Invoice::DRAFT]);
        $grid->addSelectFilter(
            'invoice_status',
            'i.invoiceStatus',
            'Status',
            array_map(
                function ($value) {
                    return $this->gridHelper->trans($value);
                },
                $statusFilter
            )
        );
        $grid->addDateFilter('created_date', 'i.createdDate', 'Created date', true);

        $printed = $grid->addBoolFilter('is_printed', 'i.pdfBatchPrinted', 'Printed');
        $printed->setLabelIcon('ucrm-icon--printer');

        $overdue = $grid->addBoolFilter(
            'overdue',
            '(CASE WHEN i.invoiceStatus IN (:unpaid) AND i.dueDate < :today THEN true ELSE false END)',
            'Overdue'
        );
        $overdue->setLabelIcon('ucrm-icon--danger-fill');

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

        $voidMultiAction = $grid->addMultiAction(
            'void',
            'Void',
            function () use ($grid) {
                return $this->multiVoidAction($grid);
            },
            [
                'button--warning',
            ],
            'Do you really want to void these invoices?',
            null,
            'ucrm-icon--void'
        );
        $voidMultiAction->confirmOkay = 'Void forever';

        $deleteMultiAction = $grid->addMultiAction(
            'delete',
            'Delete',
            function () use ($grid) {
                return $this->multiDeleteAction($grid);
            },
            [
                'button--danger',
            ],
            'Do you really want to delete these invoices?',
            null,
            'ucrm-icon--trash'
        );
        $deleteMultiAction->confirmOkay = 'Delete forever';

        $exportPdf = $grid->addMultiAction(
            'export-pdf',
            'Export PDF',
            function () use ($grid) {
                return $this->exportPdfOverviewAction($grid);
            },
            [
                'button--primary',
            ],
            null,
            'Exports filtered invoices into PDF file as a table.<br><small><em>Void and draft invoices are never included.</em></small>',
            null,
            true,
            false
        );

        $exportCsv = $grid->addMultiAction(
            'export-csv',
            'Export CSV',
            function () use ($grid) {
                return $this->exportCsvOverviewAction($grid);
            },
            [
                'button--primary',
            ],
            null,
            'Exports filtered invoices into CSV file.<br><small><em>Void and draft invoices are never included.</em></small>',
            null,
            true,
            false
        );

        $exportPrint = $grid->addMultiAction(
            'export-print',
            'Print',
            function () use ($grid) {
                return $this->exportPdfAction($grid);
            },
            [],
            null,
            'Exports all filtered invoices into single PDF file and marks them as printed.<br><small><em>Void and draft invoices are never included.</em></small>',
            null,
            true,
            false
        );

        $group = new MultiActionGroup(
            'export',
            'Export',
            [
                'button--primary',
            ],
            [
                $exportPdf,
                $exportCsv,
                $exportPrint,
            ],
            'ucrm-icon--export'
        );
        $grid->addMultiActionGroup($group);

        return $grid;
    }
}
