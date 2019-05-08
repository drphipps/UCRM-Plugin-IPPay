<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Grid\Outage;

use AppBundle\Component\Grid\Column\BaseColumn;
use AppBundle\Component\Grid\Filter\SelectFilterField;
use AppBundle\Component\Grid\Grid;
use AppBundle\Controller\OutageController;
use AppBundle\Entity\DeviceOutage;
use AppBundle\Util\Formatter;
use Doctrine\ORM\QueryBuilder;

class NetworkDeviceOutageGridFactory extends BaseOutageGridFactory
{
    public function create(): Grid
    {
        $qb = $this->deviceOutageProvider->getNetworkQueryBuilder();
        $grid = $this->gridFactory->createGrid($qb, __CLASS__);

        $grid->addIdentifier('do_id', 'do.id');
        $grid->addIdentifier('d_id', 'd.id');
        $grid->addIdentifier('do_end', 'do.outageEnd');
        $grid->addIdentifier('d_name', 'd.name');
        $grid->setDefaultSort('do_start', Grid::DESC);
        $grid->addRouterUrlParam('filterType', OutageController::NETWORK_DEVICES);
        $grid->setRowUrl('device_show', 'd');

        $grid->attached();

        $grid
            ->addRawCustomColumn(
                'd_name',
                'Device',
                function ($row) {
                    return $this->renderDeviceStatusBall($row['d_status'], $row['d_name']);
                }
            )
            ->setSortable();

        $grid
            ->addTwigFilterColumn(
                'do_start',
                'do.outageStart',
                'Start',
                'localizedDate',
                [Formatter::DEFAULT, Formatter::SHORT]
            )
            ->setSortable();

        $grid
            ->addCustomColumn(
                'do_end',
                'End',
                function ($row) {
                    return $row['do_end']
                        ? $this->formatter->formatDate(
                            $row['do_end'],
                            Formatter::DEFAULT,
                            Formatter::SHORT
                        )
                        : BaseColumn::EMPTY_COLUMN;
                }
            )
            ->setSortable();

        $grid->addCustomColumn(
            'duration',
            'Duration',
            function ($row) {
                /** @var DeviceOutage $outage */
                $outage = $row[0];

                return $this->durationFormatter->format($outage->getDuration());
            }
        );

        $filter = $grid->addRadioFilter(
            'exclude-ended',
            'do.outageEnd',
            'Filter',
            array_map(
                function ($value) {
                    return $this->gridHelper->trans($value);
                },
                OutageController::EXCLUDE_ENDED_FILTER
            ),
            true
        );
        $filter->setDefaultValue(1);

        $sites = $this->deviceOutageProvider->getAllSitesForm();
        $grid->addSelectFilter('site', 's.id', 'Site', $sites, true);

        $devices = $this->deviceOutageProvider->getDevicesForm();
        $grid->addSelectFilter('device', 'd.id', 'Device', $devices, true);

        $filter = $grid->addSelectFilter(
            'outage_notifications',
            'd.sendPingNotifications',
            'Outage notifications',
            [
                1 => 'With notifications',
                2 => 'Without notifications',
                3 => '-',
            ]
        );
        $filter->setDefaultValue(1);
        $filter->setAllowClear(false);
        $filter->setFilterCallback(
            function (QueryBuilder $model, $value, SelectFilterField $filter) {
                $value = (int) $value;

                if (in_array($value, [1, 2], true)) {
                    $model
                        ->andWhere(sprintf('%s = :%s', $filter->getQueryIdentifier(), $filter->getName()))
                        ->setParameter($filter->getName(), $value === 1);
                }
            }
        );

        return $grid;
    }
}
