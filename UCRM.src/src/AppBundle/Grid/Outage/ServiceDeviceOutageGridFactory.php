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
use AppBundle\Entity\ServiceDeviceOutage;
use AppBundle\Util\Formatter;
use Doctrine\ORM\QueryBuilder;

class ServiceDeviceOutageGridFactory extends BaseOutageGridFactory
{
    public function create(): Grid
    {
        $qb = $this->deviceOutageProvider->getServiceQueryBuilder();
        $grid = $this->gridFactory->createGrid($qb, __CLASS__);

        $grid->addIdentifier('do_id', 'do.id');
        $grid->addIdentifier('s_id', 's.id');
        $grid->addIdentifier('do_end', 'do.outageEnd');
        $grid->setDefaultSort('do_start', Grid::DESC);
        $grid->addRouterUrlParam('filterType', OutageController::SERVICE_DEVICES);
        $grid->setRowUrl('client_service_show', 's');

        $grid->attached();

        $grid->addRawCustomColumn(
            'd_service',
            'Service',
            function ($row) {
                /** @var ServiceDeviceOutage $outage */
                $outage = $row[0];

                return $this->renderDeviceStatusBall(
                    $outage->getServiceDevice()->getStatus(),
                    $outage->getServiceDevice()->getService()->getName()
                );
            }
        );

        $grid->addCustomColumn(
            'd_client',
            'Client',
            function ($row) {
                /** @var ServiceDeviceOutage $outage */
                $outage = $row[0];

                return $outage->getServiceDevice()->getService()->getClient()->getNameForView()
                    ?: BaseColumn::EMPTY_COLUMN;
            }
        );

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

        $clients = $this->deviceOutageProvider->getAllClientsForm();
        $grid->addSelectFilter('client', 'c.id', 'Client', $clients, true);

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
