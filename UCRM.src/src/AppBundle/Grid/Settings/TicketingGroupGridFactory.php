<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\Settings;

use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Controller\SettingTicketingController;
use TicketingBundle\DataProvider\TicketGroupDataProvider;

class TicketingGroupGridFactory
{
    /**
     * @var GridFactory
     */
    private $gridFactory;

    /**
     * @var TicketGroupDataProvider
     */
    private $ticketGroupDataProvider;

    public function __construct(GridFactory $gridFactory, TicketGroupDataProvider $ticketGroupDataProvider)
    {
        $this->gridFactory = $gridFactory;
        $this->ticketGroupDataProvider = $ticketGroupDataProvider;
    }

    public function create(): Grid
    {
        $qb = $this->ticketGroupDataProvider->getGridModel();
        $grid = $this->gridFactory->createGrid($qb, __CLASS__, 'tg_name', Grid::ASC);
        $grid->addIdentifier('tg_id', 'tg.id');
        $grid->attached();

        $grid->addTextColumn('tg_name', 'tg.name', 'Name')->setSortable();

        $grid->addEditActionButton('setting_ticketing_group_edit', [], SettingTicketingController::class, true);

        $grid->addDeleteActionButton('setting_ticketing_group_delete', [], SettingTicketingController::class);

        return $grid;
    }
}
