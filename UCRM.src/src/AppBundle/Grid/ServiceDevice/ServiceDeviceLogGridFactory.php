<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\ServiceDevice;

use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\Entity\NetworkDeviceLogInterface;
use AppBundle\Entity\ServiceDevice;
use AppBundle\Entity\ServiceDeviceLog;
use AppBundle\Facade\ServiceDeviceLogFacade;
use AppBundle\Util\Formatter;

class ServiceDeviceLogGridFactory
{
    /**
     * @var GridFactory
     */
    private $gridFactory;

    /**
     * @var GridHelper
     */
    private $gridHelper;

    /**
     * @var ServiceDeviceLogFacade
     */
    private $deviceLogFacade;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    public function __construct(
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        ServiceDeviceLogFacade $deviceLogFacade,
        \Twig_Environment $twig
    ) {
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->deviceLogFacade = $deviceLogFacade;
        $this->twig = $twig;
    }

    public function create(ServiceDevice $device): Grid
    {
        $qb = $this->deviceLogFacade->getGridModel($device);

        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->setDefaultSort(null);
        $grid->addIdentifier('dl_id', 'dl.id');
        $grid->addRouterUrlParam('id', $device->getService()->getId());
        $grid->setRouterUrlSuffix(sprintf('#tab-service-device-log-%d', $device->getId()));

        $grid->attached();

        $grid
            ->addTwigFilterColumn(
                'dl_created_date',
                'dl.createdDate',
                'Created date',
                'localizedDate',
                [Formatter::DEFAULT, Formatter::SHORT]
            )
            ->setWidth(30);

        $grid->addRawCustomColumn(
            'dl_message',
            'Message',
            function ($row) {
                /** @var ServiceDeviceLog $deviceLog */
                $deviceLog = $row[0];

                return $this->renderStatusBall($deviceLog->getStatus(), $deviceLog->getMessage());
            }
        );

        return $grid;
    }

    protected function renderStatusBall(int $status, string $label): string
    {
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
            default:
                $type = '';
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
