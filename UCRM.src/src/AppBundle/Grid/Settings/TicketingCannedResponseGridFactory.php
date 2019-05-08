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
use TicketingBundle\DataProvider\TicketCannedResponseDataProvider;

class TicketingCannedResponseGridFactory
{
    private $gridFactory;

    private $ticketCannedResponseDataProvider;

    public function __construct(
        GridFactory $gridFactory,
        TicketCannedResponseDataProvider $ticketCannedResponseDataProvider
    ) {
        $this->gridFactory = $gridFactory;
        $this->ticketCannedResponseDataProvider = $ticketCannedResponseDataProvider;
    }

    public function create(): Grid
    {
        $qb = $this->ticketCannedResponseDataProvider->getGridModel();
        $grid = $this->gridFactory->createGrid($qb, __CLASS__, 'tcr_name', Grid::ASC);
        $grid->addIdentifier('tcr_id', 'tcr.id');
        $grid->attached();

        $grid->addTextColumn('tcr_name', 'tcr.name', 'Name')->setSortable();

        $grid->addEditActionButton(
            'setting_ticketing_canned_response_edit',
            [],
            SettingTicketingController::class,
            true
        );

        $grid->addDeleteActionButton('setting_ticketing_canned_response_delete', [], SettingTicketingController::class);

        return $grid;
    }
}
