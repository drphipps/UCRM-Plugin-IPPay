<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Grid\Invoice;

use AppBundle\Component\Elastic\Search;
use AppBundle\Component\Grid\Column\BaseColumn;
use AppBundle\Component\Grid\Component\MultiActionGroup;
use AppBundle\Component\Grid\Grid;
use AppBundle\Controller\InvoiceController;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Option;
use AppBundle\Security\Permission;
use AppBundle\Util\Formatter;
use Doctrine\ORM\QueryBuilder;

class InvoiceGridFactory extends BaseInvoiceGridFactory
{
    public function createInvoiceGrid(): Grid
    {
        return $this->create(false);
    }

    public function createProformaGrid(): Grid
    {
        return $this->create(true);
    }

    private function create(bool $proformaOnly): Grid
    {
        $qb = $this->invoiceDataProvider->getGridModel()
            ->addSelect('COALESCE(c.sendInvoiceByPost, :defaultSendByPost) AS c_send_invoice_by_post')
            ->andWhere('i.invoiceStatus != :invoiceStatusDraft')
            ->andWhere('i.isProforma = :isProforma')
            ->setParameter('invoiceStatusDraft', Invoice::DRAFT)
            ->setParameter('defaultSendByPost', $this->gridHelper->getOption(Option::SEND_INVOICE_BY_POST))
            ->setParameter('isProforma', $proformaOnly);

        $grid = $this->gridFactory->createGrid($qb, __CLASS__, 'i_id');
        $grid->setRowUrl('billing_invoice_show');
        $grid->setDefaultSort('i_created_date', Grid::DESC);
        $grid->addIdentifier('i_id', 'i.id');
        $grid->addIdentifier('i_due_date', 'i.dueDate');
        $grid->setPostFetchCallback($this->invoiceDataProvider->getGridPostFetchCallback());

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
                        $this->badgeFactory->createInvoiceStatusBadge($row['i_invoice_status']),
                        $this->badgeFactory->createInvoiceUncollectibleBadge($invoice)
                    );
                }
            )
            ->setSortable()
            ->setIsGrouped();

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

        $sendButton = $grid->addActionButton(
            'billing_invoice_send_invoice',
            ['id'],
            InvoiceController::class,
            Permission::EDIT
        );
        $sendButton->setIcon('ucrm-icon--email');
        $sendButton->setCssClasses(['button--warning-o', 'warning']);
        $sendButton->setData(
            [
                'tooltip' => $this->gridHelper->trans('Send invoice'),
                'confirm' => 'Do you really want to send invoice to client?',
                'confirm-title' => 'Send invoice to client',
                'confirm-okay' => 'Send',
            ]
        );
        $sendButton->addRenderCondition(
            function ($row) {
                /** @var Invoice $invoice */
                $invoice = $row[0];

                $includeZeroBalance = $this->gridHelper->getOption(Option::SEND_INVOICE_WITH_ZERO_BALANCE);

                return in_array(
                        $invoice->getInvoiceStatus(),
                        Invoice::VALID_STATUSES,
                        true
                    )
                    && ! $invoice->getEmailSentDate()
                    && (
                        $includeZeroBalance
                        || round($invoice->getAmountToPay(), 2) > 0.0
                    );
            }
        );
        $sendButton->addDisabledCondition(
            function ($row) {
                /** @var Invoice $invoice */
                $invoice = $row[0];

                return ! $invoice->getClient()->hasBillingEmail();
            }
        );
        $sendButton->setDisabledTooltip($this->gridHelper->trans('Client does not have Billing email.'));
        $sendButton->setDisabledCssClasses(
            [
                'button',
                'button--transparent',
                'button--icon-only',
                'danger',
                'is-disabled--opaque',
            ],
            true
        );
        $sendButton->setDisabledIcon('ucrm-icon--at-cross');

        $editButton = $grid->addEditActionButton('client_invoice_edit', [], InvoiceController::class);
        $editButton->addRenderCondition(
            function ($row) {
                /** @var Invoice $invoice */
                $invoice = $row[0];

                return $invoice->isEditable();
            }
        );
        $editButton->setRenderSubstitute(true);

        $organizations = $this->organizationFacade->findAllForm();
        $showOrganizations = count($organizations) > 1;
        if ($showOrganizations) {
            $grid->addCustomColumn(
                'o_name',
                'Organization',
                function ($row) {
                    return $row['o_name'] ?: BaseColumn::EMPTY_COLUMN;
                }
            );
        }

        $tooltip = sprintf(
            '%s%s',
            $this->gridHelper->trans('Search by invoice number, client name or email'),
            html_entity_decode('&hellip;')
        );
        $grid->addElasticFilter('search', 'i.id', $tooltip, Search::TYPE_INVOICE, $this->gridHelper->trans('Search'));

        $statusFilter = Invoice::STATUS_REPLACE_STRING;
        unset($statusFilter[Invoice::DRAFT]);

        if ($proformaOnly) {
            unset($statusFilter[Invoice::PARTIAL], $statusFilter[Invoice::PAID]);
        } else {
            unset($statusFilter[Invoice::PROFORMA_PROCESSED]);
        }

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
        $sendByPost = $grid->addBoolFilter(
            'send_by_post',
            'COALESCE(c.sendInvoiceByPost, :defaultSendByPost)',
            'Send by post'
        );
        $sendByPost->setLabelIcon('ucrm-icon--envelope');
        $printed = $grid->addBoolFilter('is_printed', 'i.pdfBatchPrinted', 'Printed');
        $printed->setLabelIcon('ucrm-icon--printer');
        $overdue = $grid->addBoolFilter(
            'overdue',
            '(CASE WHEN i.invoiceStatus IN (:unpaid) AND i.dueDate < :today THEN true ELSE false END)',
            'Overdue'
        );
        $overdue->setLabelIcon('ucrm-icon--danger-fill');

        if ($showOrganizations) {
            $grid->addSelectFilter('organization', 'o.id', 'Organization', $organizations);
        }

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
