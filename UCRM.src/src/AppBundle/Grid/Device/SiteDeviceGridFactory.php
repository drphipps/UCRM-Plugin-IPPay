<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Grid\Device;

use AppBundle\Component\Grid\Grid;
use AppBundle\Controller\DeviceController;
use AppBundle\Entity\Site;

class SiteDeviceGridFactory extends BaseDeviceGridFactory
{
    public function create(Site $site): Grid
    {
        $qb = $this->deviceFacade->getGridModel();
        $qb->andWhere('s.id = :siteId')
            ->setParameter('siteId', $site->getId());

        $grid = $this->gridFactory->createGrid($qb, __CLASS__);

        $grid->setDefaultSort('d_name');
        $grid->addIdentifier('d_id', 'd.id');
        $grid->addIdentifier('d_name', 'd.name');
        $grid->addRouterUrlParam('id', $site->getId());
        $grid->setRowUrl('device_show');

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

        $grid->addEditActionButton('device_edit', [], DeviceController::class);
        $grid->addDeleteActionButton('device_delete', [], DeviceController::class);

        return $grid;
    }
}
