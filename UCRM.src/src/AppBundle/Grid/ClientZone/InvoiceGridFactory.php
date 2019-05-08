<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Grid\ClientZone;

use AppBundle\Component\Grid\Grid;
use AppBundle\Entity\Client;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Grid\Invoice\BaseInvoiceGridFactory;
use AppBundle\Util\Formatter;
use Doctrine\ORM\QueryBuilder;

class InvoiceGridFactory extends BaseInvoiceGridFactory
{
    public function create(Client $client): Grid
    {
        $qb = $this->invoiceDataProvider->getGridModel($client)
            ->addSelect('i.pdfPath as i_pdf_path')
            ->andWhere('i.invoiceStatus != :invoiceStatusDraft')
            ->setParameter('invoiceStatusDraft', Invoice::DRAFT);

        $invoiceIdsWithPendingPayments = $this->paymentTokenDataProvider->getInvoiceIdsWithPendingPayments($client);

        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->setRowUrl('client_zone_invoice_show');
        $grid->addIdentifier('i_id', 'i.id');
        $grid->addIdentifier('i_due_date', 'i.dueDate');
        $grid->setDefaultSort('i_created_date', Grid::DESC);
        $grid->setActionsColumnCssClass('grid-hidden--smDown');
        $grid->attached();

        $grid
            ->addRawCustomColumn(
                'i_invoice_number',
                'Invoice number',
                function ($row) use ($invoiceIdsWithPendingPayments) {
                    /** @var Invoice $invoice */
                    $invoice = $row[0];
                    $pending = '';
                    if (in_array($invoice->getId(), $invoiceIdsWithPendingPayments, true)) {
                        $pending = '<span class="appType--micro"> - ' . $this->gridHelper->trans('Payment pending') . '</span>';
                    }

                    return sprintf(
                        '%s%s%s',
                        htmlspecialchars($row['i_invoice_number'] ?? '', ENT_QUOTES),
                        $this->badgeFactory->createInvoiceStatusBadge($row['i_invoice_status']),
                        $pending
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
            ->setAlignRight()
            ->setCssClass('grid-hidden--smDown');

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
            ->setCssClass('grid-hidden--smDown')
            ->setOrderByCallback(
                function (QueryBuilder $model, string $direction) {
                    $model->orderBy('i.createdDate', $direction);
                    $model->addOrderBy('i.id', $direction);
                }
            );

        $grid->addRawCustomColumn('i_due_date', 'Due date', [$this, 'renderDueDate'])
            ->setSortable();

        $downloadButton = $grid->addActionButton('client_zone_invoice_download_pdf');
        $downloadButton->setTitle($this->gridHelper->trans('PDF'));
        $downloadButton->addRenderCondition(
            function ($row) {
                return (bool) $row['i_pdf_path'];
            }
        );

        if ($client->getOrganization()->hasPaymentGateway($this->gridHelper->isSandbox())) {
            $payButton = $grid->addActionButton('client_zone_invoice_pay');
            $payButton->setTitle($this->gridHelper->trans('Pay online'));
            $payButton->setCssClasses(
                [
                    'button--success-o',
                ]
            );
            $payButton->addRenderCondition(
                function ($row) use ($invoiceIdsWithPendingPayments) {
                    /** @var Invoice $invoice */
                    $invoice = $row[0];
                    if (in_array($invoice->getId(), $invoiceIdsWithPendingPayments, true)) {
                        return false;
                    }

                    return in_array($invoice->getInvoiceStatus(), Invoice::UNPAID_STATUSES, true);
                }
            );
        }

        return $grid;
    }
}
