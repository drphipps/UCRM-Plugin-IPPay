<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\Report;

use AppBundle\Component\Grid\Column\BaseColumn;
use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\DataProvider\DataUsageDataProvider;
use AppBundle\DataProvider\TariffDataProvider;
use AppBundle\Entity\ReportDataUsage;
use AppBundle\Util\Formatter;
use AppBundle\Util\Helpers;
use Nette\Utils\Html;

class DataUsageOverviewGridFactory
{
    /**
     * @var DataUsageDataProvider
     */
    private $dataUsageDataProvider;

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var GridFactory
     */
    private $gridFactory;

    /**
     * @var GridHelper
     */
    private $gridHelper;

    /**
     * @var TariffDataProvider
     */
    private $tariffDataProvider;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    public function __construct(
        DataUsageDataProvider $dataUsageDataProvider,
        Formatter $formatter,
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        TariffDataProvider $tariffDataProvider,
        \Twig_Environment $twig
    ) {
        $this->dataUsageDataProvider = $dataUsageDataProvider;
        $this->formatter = $formatter;
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->tariffDataProvider = $tariffDataProvider;
        $this->twig = $twig;
    }

    public function create(): Grid
    {
        $qb = $this->dataUsageDataProvider->getOverviewGridModel();

        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->setRowUrl('client_service_show', 's');
        $grid->addIdentifier('s_id', 's.id');
        $grid->setTextNoRows(
            $this->twig->render('report/data_usage/components/grid_text_no_row.html.twig')
        );
        $grid->attached();

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
            ->addTextColumn('s_name', 's.name', 'Service')
            ->setSortable();

        $grid
            ->addTextColumn(
                't_service_plan',
                't.name',
                'Service plan'
            );

        $grid
            ->addCustomColumn(
                't_dataUsageLimit',
                'Data usage limit',
                function ($row) {
                    return $row['t_dataUsageLimit']
                        ? $row['t_dataUsageLimit'] . ' ' . $this->gridHelper->trans('GB')
                        : BaseColumn::EMPTY_COLUMN;
                }
            )
            ->setSortable();

        $grid
            ->addCustomColumn(
                'last_period',
                'Last period',
                function ($row) {
                    /** @var ReportDataUsage $report */
                    $report = $row[0];

                    if ($report->getLastPeriodStart()) {
                        return sprintf(
                            '%s - %s',
                            $this->formatter->formatDate(
                                $report->getLastPeriodStart(),
                                Formatter::DEFAULT,
                                Formatter::NONE
                            ),
                            $this->formatter->formatDate(
                                $report->getLastPeriodEnd(),
                                Formatter::DEFAULT,
                                Formatter::NONE
                            )
                        );
                    }

                    return BaseColumn::EMPTY_COLUMN;
                }
            );

        $grid
            ->addCustomColumn(
                'last_traffic',
                'Last period traffic',
                function ($row) {
                    /** @var ReportDataUsage $report */
                    $report = $row[0];

                    if (null === $report->getLastPeriodDownload() && null === $report->getLastPeriodUpload()) {
                        return BaseColumn::EMPTY_COLUMN;
                    }

                    return sprintf(
                        '%s / %s',
                        $report->getLastPeriodDownload() !== null
                            ? Helpers::bytesToSize($report->getLastPeriodDownload())
                            : html_entity_decode('&ndash;'),
                        $report->getLastPeriodUpload() !== null
                            ? Helpers::bytesToSize($report->getLastPeriodUpload())
                            : html_entity_decode('&ndash;')
                    );
                }
            );

        $grid
            ->addRawCustomColumn(
                'last_total',
                'Last period total',
                function ($row) {
                    if (null === $row['last_total']) {
                        return BaseColumn::EMPTY_COLUMN;
                    }

                    $span = Html::el('span');
                    $span->setText(Helpers::bytesToSize($row['last_total']));

                    if ($row['t_dataUsageLimitByte'] && $row['last_total'] > $row['t_dataUsageLimitByte']) {
                        $span->setAttribute('class', 'danger');
                    }

                    return (string) $span;
                }
            )
            ->setSortable();

        $grid
            ->addCustomColumn(
                'current_period',
                'Current period',
                function ($row) {
                    if ($row['current_total'] === null) {
                        return BaseColumn::EMPTY_COLUMN;
                    }

                    /** @var ReportDataUsage $report */
                    $report = $row[0];

                    return sprintf(
                        '%s - %s',
                        $this->formatter->formatDate(
                            $report->getCurrentPeriodStart(),
                            Formatter::DEFAULT,
                            Formatter::NONE
                        ),
                        $this->formatter->formatDate(
                            $report->getCurrentPeriodEnd(),
                            Formatter::DEFAULT,
                            Formatter::NONE
                        )
                    );
                }
            );

        $grid
            ->addCustomColumn(
                'current_traffic',
                'Current period traffic',
                function ($row) {
                    if ($row['current_total'] === null) {
                        return BaseColumn::EMPTY_COLUMN;
                    }
                    /** @var ReportDataUsage $report */
                    $report = $row[0];

                    return sprintf(
                        '%s / %s',
                        Helpers::bytesToSize($report->getCurrentPeriodDownload()),
                        Helpers::bytesToSize($report->getCurrentPeriodUpload())
                    );
                }
            );

        $grid
            ->addRawCustomColumn(
                'current_total',
                'Current period total',
                function ($row) {
                    if ($row['current_total'] === null) {
                        return BaseColumn::EMPTY_COLUMN;
                    }

                    $span = Html::el('span');
                    $span->setText(Helpers::bytesToSize($row['current_total']));

                    if ($row['t_dataUsageLimitByte'] && $row['current_total'] > $row['t_dataUsageLimitByte']) {
                        $span->setAttribute('class', 'danger');
                    }

                    return (string) $span;
                }
            )
            ->setSortable();

        $options = $this->tariffDataProvider->getActiveTariffNamesForForm();
        if ($options) {
            $grid->addMultipleSelectFilter(
                'service_plan',
                't.id',
                'Service plan',
                $options,
                'Service plan'
            );
        }

        return $grid;
    }
}
