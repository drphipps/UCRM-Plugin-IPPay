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
use AppBundle\DataProvider\DataUsageDataProvider;

class DataUsageGridFactory
{
    /**
     * @var GridFactory
     */
    private $gridFactory;

    /**
     * @var DataUsageDataProvider
     */
    private $dataUsageGridDataProvider;

    public function __construct(GridFactory $gridFactory, DataUsageDataProvider $dataUsageGridDataProvider)
    {
        $this->gridFactory = $gridFactory;
        $this->dataUsageGridDataProvider = $dataUsageGridDataProvider;
    }

    public function create(string $period): Grid
    {
        $qb = $this->dataUsageGridDataProvider->getGridModel($period);

        $grid = $this->gridFactory->createGrid($qb, __CLASS__, 'c_traffic_download');
        $grid->setRowUrl('redirect_client_to_unique_service');
        $grid->addIdentifier('c_id', 'c.id');
        $grid->addRouterUrlParam('period', $period);

        $grid->attached();

        $grid
            ->addTextColumn('c_clientId', 'c.id', 'ID')
            ->setWidth(8)
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
            ->addTwigFilterColumn(
                'c_traffic_download',
                'SUM(a.download)',
                'Downloaded data',
                'bytesToSize'
            )
            ->setSortable();

        $grid
            ->addTwigFilterColumn(
                'c_traffic_upload',
                'SUM(a.upload)',
                'Uploaded data',
                'bytesToSize'
            )
            ->setSortable();

        return $grid;
    }
}
