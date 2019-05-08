<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Grid\Payment;

use AppBundle\Component\Grid\Column\BaseColumn;
use AppBundle\Component\Grid\Component\MultiActionGroup;
use AppBundle\Component\Grid\Grid;
use AppBundle\Entity\Client;
use AppBundle\Entity\Payment;
use AppBundle\Entity\User;
use AppBundle\Util\Formatter;
use Doctrine\ORM\QueryBuilder;
use Nette\Utils\Html;

class ClientPaymentGridFactory extends BasePaymentGridFactory
{
    public function create(Client $client): Grid
    {
        $qb = $this->paymentDataProvider->getGridModel(false);
        $qb
            ->andWhere('p.client = :clientId')
            ->setParameter('clientId', $client->getId());

        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->addIdentifier('p_id', 'p.id');
        $grid->addRouterUrlParam('id', $client->getId());
        $grid->setRowUrl('payment_show');
        $grid->setDefaultSort('p_created_date', Grid::DESC);

        $grid->attached();

        $grid
            ->addCustomColumn(
                'p_method',
                'Method',
                function ($row) {
                    /** @var Payment $payment */
                    $payment = $row[0];

                    return $this->gridHelper->trans($payment->getMethodName());
                }
            )
            ->setSortable();

        $grid
            ->addCustomColumn(
                'p_amount',
                'Amount',
                function ($row) {
                    /** @var Payment $payment */
                    $payment = $row[0];

                    return $this->formatter->formatCurrency(
                        $payment->getAmount(),
                        $payment->getCurrency() ? $payment->getCurrency()->getCode() : null,
                        $payment->getClient() ? $payment->getClient()->getOrganization()->getLocale() : null
                    );
                }
            )
            ->setSortable()
            ->setAlignRight();

        $grid
            ->addTwigFilterColumn(
                'p_created_date',
                'p.createdDate',
                'Created date',
                'localizedDate',
                [Formatter::DEFAULT, Formatter::SHORT]
            )
            ->setSortable()
            ->setOrderByCallback(
                function (QueryBuilder $model, string $direction) {
                    $model->orderBy('p.createdDate', $direction);
                    $model->addOrderBy('p.id', $direction);
                }
            );

        $grid->addDateFilter('created_date', 'p.createdDate', 'Created date', true);

        $grid
            ->addRawCustomColumn(
                'au_user',
                'Created by',
                function ($row) {
                    /** @var Payment $payment */
                    $payment = $row[0];

                    if (! $payment->getUser()) {
                        return BaseColumn::EMPTY_COLUMN;
                    }

                    /** @var User $user */
                    $user = $payment->getUser();
                    if (! $user->isDeleted()) {
                        return $user->getNameForView();
                    }

                    $span = Html::el(
                        'span',
                        [
                            'class' => 'appType--quiet',
                        ]
                    );
                    $span->setText($user->getNameForView());

                    return $span;
                }
            )
            ->setSortable();

        $grid->addNumberRangeFilter('amount', 'p.amount', 'Amount');

        $methods = $this->getPaymentMethodsForFilter($client);
        if ($methods) {
            $grid->addSelectFilter('method', 'p.method', 'Method', $methods);
        }

        $grid->addMultiAction(
            'unmatch',
            'Unmatch',
            function () use ($grid) {
                return $this->multiUnmatchAction($grid);
            },
            [
                'button--warning',
            ],
            'Do you really want to unmatch these payments?',
            null,
            'ucrm-icon--unlink'
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
            'Do you really want to delete these payments?',
            null,
            'ucrm-icon--trash'
        );
        $deleteMultiAction->confirmOkay = 'Delete forever';

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
            'Exports filtered payments into CSV file as a table.',
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
            'Exports filtered payments into PDF file as a table.',
            null,
            true,
            false
        );

        $exportReceiptPdf = $grid->addMultiAction(
            'export-receipt-pdf',
            'Export payment receipt PDF',
            function () use ($grid) {
                return $this->exportPdfReceiptAction($grid);
            },
            [
                'button--primary',
            ],
            null,
            'Exports filtered payment receipts into PDF file.',
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
                $exportReceiptPdf,
            ],
            'ucrm-icon--export'
        );
        $grid->addMultiActionGroup($group);

        return $grid;
    }
}
