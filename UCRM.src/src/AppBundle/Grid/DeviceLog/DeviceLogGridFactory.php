<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Grid\DeviceLog;

use AppBundle\Component\Grid\Column\BaseColumn;
use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Entity\Device;
use AppBundle\Entity\DeviceLog;
use AppBundle\Entity\NetworkDeviceLogInterface;
use AppBundle\Entity\Site;
use AppBundle\Facade\DeviceLogFacade;
use AppBundle\Util\Formatter;

class DeviceLogGridFactory
{
    /**
     * @var GridFactory
     */
    private $gridFactory;

    /**
     * @var DeviceLogFacade
     */
    private $deviceLogFacade;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    public function __construct(GridFactory $gridFactory, DeviceLogFacade $deviceLogFacade, \Twig_Environment $twig)
    {
        $this->gridFactory = $gridFactory;
        $this->deviceLogFacade = $deviceLogFacade;
        $this->twig = $twig;
    }

    public function create(Device $device = null, Site $site = null): Grid
    {
        $ids = [];
        if ($device) {
            $ids[] = $device->getId();
        }

        if ($site) {
            $ids = array_merge(
                $ids,
                $site
                    ->getNotDeletedDevices()
                    ->map(
                        function (Device $device) {
                            return $device->getId();
                        }
                    )
                    ->toArray()
            );
        }

        $qb = $this->deviceLogFacade->getGridModel($ids);
        $grid = $this->gridFactory->createGrid(
            $qb,
            sprintf(
                '%s_%s_%s',
                __CLASS__,
                $device ? $device->getId() : '',
                $site ? $site->getId() : ''
            )
        );
        $grid->addIdentifier('dl_id', 'dl.id');
        $grid->addIdentifier('d_id', 'd.id');
        $grid->setDefaultSort(null);

        if (! $device) {
            $grid->setRowUrl('device_show', 'd');
        }

        if ($site) {
            $grid->addRouterUrlParam('id', $site->getId());
            $grid->setRouterUrlSuffix('#tab-device-log');
        } elseif ($device) {
            $grid->addRouterUrlParam('id', $device->getId());
            $grid->setRouterUrlSuffix('#tab-log');
        }

        $grid->attached();

        $grid
            ->addTwigFilterColumn(
                'dl_created_date',
                'dl.createdDate',
                'Created date',
                'localizedDate',
                [Formatter::DEFAULT, Formatter::MEDIUM]
            )
            ->setSortable()
            ->setWidth(20);

        $grid->addRawCustomColumn(
            'dl_message',
            'Message',
            function ($row) {
                /** @var DeviceLog $log */
                $log = $row[0];

                return $this->renderStatusBall($log->getStatus(), $log->getMessage());
            }
        );

        if (! $device) {
            $grid->addCustomColumn(
                'd_name',
                'Device',
                function ($row) {
                    /** @var DeviceLog $log */
                    $log = $row[0];

                    return $log->getDevice()->getNameWithSite() ?: BaseColumn::EMPTY_COLUMN;
                }
            );
        }

        return $grid;
    }

    private function renderStatusBall(int $status, string $label): string
    {
        $type = '';

        switch ($status) {
            case NetworkDeviceLogInterface::STATUS_OK:
                $type = 'success';
                break;
            case NetworkDeviceLogInterface::STATUS_ERROR:
                $type = 'danger';
                break;
            case NetworkDeviceLogInterface::STATUS_WARNING:
                $type = 'warning';
                break;
        }

        return $this->twig->render(
            'client/components/view/status_ball.html.twig',
            [
                'type' => $type,
                'label' => $label,
            ]
        );
    }
}
