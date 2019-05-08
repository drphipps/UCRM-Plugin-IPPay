<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Grid\ServiceDevice;

use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Entity\Device;
use AppBundle\Facade\ServiceDeviceFacade;
use AppBundle\Util\Formatter;

class UnknownServiceDeviceGridFactory
{
    /**
     * @var GridFactory
     */
    private $gridFactory;

    /**
     * @var ServiceDeviceFacade
     */
    private $serviceDeviceFacade;

    public function __construct(GridFactory $gridFactory, ServiceDeviceFacade $serviceDeviceFacade)
    {
        $this->gridFactory = $gridFactory;
        $this->serviceDeviceFacade = $serviceDeviceFacade;
    }

    /**
     * @param Device $device
     */
    public function create(Device $device = null): Grid
    {
        if ($device) {
            $qb = $this->serviceDeviceFacade->getUnknownGridModelByDevice($device);
        } else {
            $qb = $this->serviceDeviceFacade->getUnknownGridModel();
        }

        $grid = $this->gridFactory->createGrid($qb, __CLASS__ . ($device ? $device->getId() : ''));
        $grid->addIdentifier('sd_id', 'sd.id');
        $grid->setDefaultSort('sd_last_seen', Grid::DESC);

        if ($device) {
            $grid->addRouterUrlParam('id', $device->getId());
            $grid->setRouterUrlSuffix('#tab-unknown-service-devices');
        } else {
            $grid->setRouterUrlSuffix('#tab-service-devices');
        }

        $grid->attached();

        if (! $device) {
            $grid->addTextColumn('d_name', 'd.name', 'Device')
                ->setSortable();
        }

        $grid->addTextColumn('i_name', 'i.name', 'Interface')
            ->setSortable();
        $grid->addTwigFilterColumn('sd_mac_address', 'sd.macAddress', 'MAC address', 'mac')
            ->setSortable();
        $grid->addTextColumn('sd_last_ip', 'sd.lastIp', 'Last IP')
            ->setSortable();
        $grid
            ->addTwigFilterColumn(
                'sd_first_seen',
                'sd.firstSeen',
                'First seen',
                'localizedDate',
                [Formatter::DEFAULT, Formatter::SHORT]
            )
            ->setSortable();
        $grid
            ->addTwigFilterColumn(
                'sd_last_seen',
                'sd.lastSeen',
                'Last seen',
                'localizedDate',
                [Formatter::DEFAULT, Formatter::SHORT]
            )
            ->setSortable();

        return $grid;
    }
}
