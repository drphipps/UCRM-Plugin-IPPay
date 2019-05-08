<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\Invoice;

use AppBundle\Component\Grid\Column\BaseColumn;
use AppBundle\Component\Grid\Component\MultiActionGroup;
use AppBundle\Component\Grid\Grid;
use AppBundle\Controller\InvoicedRevenueController;
use AppBundle\Database\UtcDateTimeType;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Util\Formatter;
use Doctrine\ORM\QueryBuilder;

class InvoicedRevenueGridFactory extends BaseInvoiceGridFactory
{
    const FILTER_DATE = 'created_date';
    const FILTER_DATE_FROM = self::FILTER_DATE . '_from';
    const FILTER_DATE_TO = self::FILTER_DATE . '_to';
    const FILTER_ORGANIZATION = 'organization';

    public function create(string $filterType): Grid
    {
        $qb = $this->invoiceDataProvider->getGridModel();

        switch ($filterType) {
            case InvoicedRevenueController::ALL_INVOICES:
                $qb->andWhere('i.invoiceStatus in (:statuses)')
                    ->setParameter(
                        'statuses',
                        [
                            Invoice::PAID,
                            Invoice::UNPAID,
                            Invoice::PARTIAL,
                        ]
                    );
                break;
            case InvoicedRevenueController::UNPAID_INVOICES:
                $qb->andWhere('i.invoiceStatus in (:statuses)')
                    ->setParameter(
                        'statuses',
                        [
                            Invoice::UNPAID,
                            Invoice::PARTIAL,
                        ]
                    );
                break;
            case InvoicedRevenueController::OVERDUE_INVOICES:
                $qb->andWhere('i.invoiceStatus in (:statuses)')
                    ->andWhere('i.dueDate < :now')
                    ->setParameter(
                        'statuses',
                        [
                            Invoice::UNPAID,
                            Invoice::PARTIAL,
                        ]
                    )
                    ->setParameter('now', new \DateTime(), UtcDateTimeType::NAME);
                break;
            case InvoicedRevenueController::PAID_INVOICES:
                $qb->andWhere('i.invoiceStatus in (:statuses)')
                    ->setParameter(
                        'statuses',
                        [
                            Invoice::PAID,
                            Invoice::PARTIAL,
                        ]
                    );
                break;
        }

        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->addIdentifier('i_id', 'i.id');
        $grid->addIdentifier('client_id', 'c.id');
        $grid->addIdentifier('i_due_date', 'i.dueDate');
        $grid->addRouterUrlParam('filterType', $filterType);
        $grid->setDefaultSort('i_created_date', Grid::DESC);
        $grid->setRowUrl('client_invoice_show');
        $grid->setAjaxEnabled(false);
        $grid->attached();

        $grid
            ->addRawCustomColumn(
                'i_invoice_number',
                'Invoice number',
                function ($row) {
                    return sprintf(
                        '%s%s',
                        htmlspecialchars($row['i_invoice_number'] ?? '', ENT_QUOTES),
                        $this->badgeFactory->createInvoiceStatusBadge($row['i_invoice_status'])
                    );
                }
            )
            ->setSortable()
            ->setIsGrouped();

        $grid
            ->addCustomColumn(
                'c_fullname',
                'Name',
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
            ->setIsGrouped()
            ->setAlignRight();

        $grid
            ->addCustomColumn(
                'i_total_taxes',
                'Taxes',
                function ($row) {
                    /** @var Invoice $invoice */
                    $invoice = $row[0];

                    return $this->formatter->formatCurrency(
                        $invoice->getTotalTaxAmount(),
                        $invoice->getCurrency()->getCode(),
                        $invoice->getOrganization()->getLocale()
                    );
                }
            )
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

        $grid->addDateFilter(self::FILTER_DATE, 'i.createdDate', 'Created date', true);

        $organizations = $this->organizationFacade->findAllForm();
        if (count($organizations) > 1) {
            $grid->addCustomColumn(
                'o_name',
                'Organization',
                function ($row) {
                    return $row['o_name'] ?: BaseColumn::EMPTY_COLUMN;
                }
            );

            $grid->addSelectFilter('organization', 'i.organization', 'Organization', $organizations);
        }

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

        $group = new MultiActionGroup(
            'export',
            'Export',
            [
                'button--primary',
            ],
            [
                $exportPdf,
                $exportCsv,
            ],
            'ucrm-icon--export'
        );
        $grid->addMultiActionGroup($group);

        return $grid;
    }
}
