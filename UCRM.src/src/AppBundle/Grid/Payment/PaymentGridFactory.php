<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Grid\Payment;

use AppBundle\Component\Elastic;
use AppBundle\Component\Grid\Column\BaseColumn;
use AppBundle\Component\Grid\Component\MultiActionGroup;
use AppBundle\Component\Grid\Grid;
use AppBundle\Controller\PaymentController;
use AppBundle\Entity\Payment;
use AppBundle\Entity\User;
use AppBundle\Security\Permission;
use AppBundle\Util\Formatter;
use Doctrine\ORM\QueryBuilder;
use Nette\Utils\Html;

class PaymentGridFactory extends BasePaymentGridFactory
{
    public function create(bool $onlyUnmatched): Grid
    {
        $qb = $this->paymentDataProvider->getGridModel($onlyUnmatched);
        $grid = $this->gridFactory->createGrid($qb, __CLASS__, 'p_id');
        $grid->setPostFetchCallback($this->paymentFacade->getGridPostFetchCallback());
        $grid->addIdentifier('p_id', 'p.id');
        $grid->setRowUrl('payment_show');

        if (empty($grid->getActiveFilter('search'))) {
            $grid->setDefaultSort('p_created_date', Grid::DESC);
        }

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
                'c_fullname',
                'Client',
                function ($row) {
                    return $row['c_fullname'] ?: BaseColumn::EMPTY_COLUMN;
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

        $grid
            ->addCustomColumn(
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

        $matchButton = $grid->addActionButton(
            'payment_match',
            [],
            PaymentController::class,
            Permission::EDIT
        );
        $matchButton->setData(
            [
                'tooltip' => $this->gridHelper->trans('Match'),
            ]
        );
        $matchButton->setIcon('ucrm-icon--link');
        $matchButton->setIsModal();

        if (! $onlyUnmatched) {
            $matchButton->addRenderCondition(
                function ($row) {
                    /** @var Payment $payment */
                    $payment = $row[0];

                    return ! $payment->isMatched();
                }
            );
        }

        $tooltip = sprintf(
            '%s%s',
            $this->gridHelper->trans('Search by name, email or payment note'),
            html_entity_decode('&hellip;')
        );
        $grid->addElasticFilter(
            'search',
            'p.id',
            $tooltip,
            Elastic\Search::TYPE_PAYMENT,
            $this->gridHelper->trans('Search')
        );

        $grid->addDateFilter('created_date', 'p.createdDate', 'Created date', true);

        $grid->addNumberRangeFilter('amount', 'p.amount', 'Amount');

        $methods = $this->getPaymentMethodsForFilter();
        if ($methods) {
            $grid->addSelectFilter('method', 'p.method', 'Method', $methods);
        }

        if (! $onlyUnmatched) {
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
        }

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

        // @todo uncomment when we decide if we want the export here
        /*
        $exportQuickBooksCsv = $grid->addMultiAction(
            'export-quickbooks',
            'Export QuickBooks Online CSV',
            function () use ($grid) {
                return $this->exportQuickBooksCsvAction($grid);
            },
            [
                'button--primary',
            ],
            null,
            'Exports filtered payments into CSV file in QuickBooks Online format.',
            null,
            true,
            false
        );
        */

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
                // $exportQuickBooksCsv,
                $exportReceiptPdf,
            ],
            'ucrm-icon--export'
        );
        $grid->addMultiActionGroup($group);

        return $grid;
    }
}
