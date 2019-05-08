<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Grid\ServiceStopReason;

use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\Controller\ServiceStopReasonController;
use AppBundle\Entity\ServiceStopReason;
use AppBundle\Facade\ServiceStopReasonFacade;

class ServiceStopReasonGridFactory
{
    /**
     * @var GridFactory
     */
    private $gridFactory;

    /**
     * @var ServiceStopReasonFacade
     */
    private $serviceStopReasonFacade;

    /**
     * @var GridHelper
     */
    private $gridHelper;

    public function __construct(
        GridFactory $gridFactory,
        ServiceStopReasonFacade $serviceStopReasonFacade,
        GridHelper $gridHelper
    ) {
        $this->gridFactory = $gridFactory;
        $this->serviceStopReasonFacade = $serviceStopReasonFacade;
        $this->gridHelper = $gridHelper;
    }

    public function create(): Grid
    {
        $qb = $this->serviceStopReasonFacade->getGridModel();
        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->addIdentifier('ssr_id', 'ssr.id');
        $grid->setRowUrl('service_stop_reason_show');

        $grid->attached();

        $grid->addCustomColumn(
            'ssr.name',
            'Name',
            function ($row) {
                /** @var ServiceStopReason $stopReason */
                $stopReason = $row[0];

                if (in_array($stopReason->getId(), ServiceStopReason::SYSTEM_REASONS, true)) {
                    return $this->gridHelper->trans($stopReason->getName(), [], 'service_stop_reason');
                }

                return $stopReason->getName();
            }
        );

        $grid->addEditActionButton('service_stop_reason_edit', [], ServiceStopReasonController::class);
        $grid
            ->addDeleteActionButton('service_stop_reason_delete', [], ServiceStopReasonController::class)
            ->addRenderCondition(
                function ($row) {
                    return $row['ssr_id'] >= ServiceStopReason::REASON_MIN_CUSTOM_ID;
                }
            );

        return $grid;
    }
}
