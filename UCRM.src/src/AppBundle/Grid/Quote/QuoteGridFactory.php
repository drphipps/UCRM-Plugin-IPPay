<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Grid\Quote;

use AppBundle\Component\Elastic\Search;
use AppBundle\Component\Grid\Column\BaseColumn;
use AppBundle\Component\Grid\Component\MultiActionGroup;
use AppBundle\Component\Grid\Grid;
use AppBundle\Controller\QuoteController;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Util\Formatter;
use Doctrine\ORM\QueryBuilder;

class QuoteGridFactory extends BaseQuoteGridFactory
{
    public function create(): Grid
    {
        $qb = $this->quoteDataProvider->getGridModel();
        $grid = $this->gridFactory->createGrid($qb, __CLASS__, 'i_id');
        $grid->setRowUrl('quote_show');
        $grid->addIdentifier('q_id', 'q.id');
        $grid->setDefaultSort('q_created_date', Grid::DESC);

        $grid->attached();

        $grid
            ->addRawCustomColumn(
                'q_quote_number',
                'Quote number',
                function ($row) {
                    return sprintf(
                        '%s%s',
                        htmlspecialchars($row['q_quote_number'] ?? '', ENT_QUOTES),
                        $this->badgeFactory->createQuoteStatusBadge($row['q_status'])
                    );
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
                'q_total',
                'Total',
                function ($row) {
                    /** @var Quote $quote */
                    $quote = $row[0];

                    return $this->formatter->formatCurrency(
                        $row['q_total'],
                        $quote->getCurrency()->getCode(),
                        $quote->getOrganization()->getLocale()
                    );
                }
            )
            ->setSortable()
            ->setAlignRight();

        $grid
            ->addTwigFilterColumn(
                'q_created_date',
                'q.createdDate',
                'Created date',
                'localizedDate',
                [Formatter::DEFAULT, Formatter::NONE]
            )
            ->setSortable()
            ->setOrderByCallback(
                function (QueryBuilder $model, string $direction) {
                    $model->orderBy('q.createdDate', $direction);
                    $model->addOrderBy('q.id', $direction);
                }
            );

        $grid->addEditActionButton('client_quote_edit', [], QuoteController::class);

        $deleteMultiAction = $grid->addMultiAction(
            'delete',
            'Delete',
            function () use ($grid) {
                return $this->multiDeleteAction($grid);
            },
            [
                'button--danger',
            ],
            'Do you really want to delete these quotes?',
            null,
            'ucrm-icon--trash'
        );
        $deleteMultiAction->confirmOkay = 'Delete forever';

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
            $this->gridHelper->trans('Search by quote number, client name, quote notes or admin notes'),
            html_entity_decode('&hellip;')
        );
        $grid->addElasticFilter('search', 'q.id', $tooltip, Search::TYPE_QUOTE, $this->gridHelper->trans('Search'));

        $clients = $this->clientDataProvider->getAllClientsForm();
        $grid->addSelectFilter('client', 'c.id', 'Client', $clients, true);

        $grid->addSelectFilter(
            'status',
            'q.status',
            'Status',
            array_map(
                function ($value) {
                    return $this->gridHelper->trans($value);
                },
                Quote::STATUSES
            )
        );
        $grid->addDateFilter('created_date', 'q.createdDate', 'Created date', true);

        if ($showOrganizations) {
            $grid->addSelectFilter('organization', 'o.id', 'Organization', $organizations);
        }

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
            'Exports filtered quotes into PDF file as a table.',
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
            'Exports filtered quotes into CSV file.',
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
            'Exports all filtered quotes into single PDF file.',
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
