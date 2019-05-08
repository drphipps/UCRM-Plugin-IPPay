<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Grid\Device;

use AppBundle\Component\Elastic;
use AppBundle\Component\Grid\Grid;
use AppBundle\Controller\DeviceController;

class DeviceGridFactory extends BaseDeviceGridFactory
{
    public function create(): Grid
    {
        $qb = $this->deviceFacade->getGridModel();

        $grid = $this->gridFactory->createGrid($qb, __CLASS__, 'd_id');
        $grid->addIdentifier('d_id', 'd.id');
        $grid->addIdentifier('d_name', 'd.name');
        $grid->setRowUrl('device_show');

        if (empty($grid->getActiveFilter('search'))) {
            $grid->setDefaultSort('d_name');
        }

        $grid->attached();

        $grid
            ->addRawCustomColumn(
                'd_name',
                'Name',
                function ($row) {
                    return $this->renderDeviceStatusBall($row['d_status'], $row['d_name']);
                }
            )
            ->setSortable();

        $grid->addTextColumn('d_modelName', 'd.modelName', 'Model name')->setSortable();
        $grid->addTextColumn('v_name', 'v.name', 'Vendor')->setSortable();
        $grid->addTextColumn('s_name', 's.name', 'Site')->setSortable();

        $tooltip = sprintf(
            '%s%s',
            $this->gridHelper->trans('Search by name, model, site, vendor or IP address'),
            html_entity_decode('&hellip;')
        );
        $grid->addElasticFilter(
            'search',
            'd.id',
            $tooltip,
            Elastic\Search::TYPE_DEVICE,
            $this->gridHelper->trans('Search')
        );

        $grid->addEditActionButton('device_edit', [], DeviceController::class);

        $grid->addMultiAction(
            'sync',
            'Sync',
            function () use ($grid) {
                return $this->multiSyncAction($grid);
            },
            [],
            null,
            null,
            'ucrm-icon--sync'
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
            'Do you really want to delete these devices?',
            null,
            'ucrm-icon--trash'
        );
        $deleteMultiAction->confirmOkay = 'Delete forever';

        return $grid;
    }
}
