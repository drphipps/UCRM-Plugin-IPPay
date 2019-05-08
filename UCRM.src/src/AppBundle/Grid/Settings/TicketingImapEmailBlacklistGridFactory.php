<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\Settings;

use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\Controller\SettingTicketingController;
use TicketingBundle\DataProvider\TicketImapEmailBlacklistDataProvider;

class TicketingImapEmailBlacklistGridFactory
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
     * @var TicketImapEmailBlacklistDataProvider
     */
    private $ticketImapEmailBlacklistDataProvider;

    public function __construct(
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        TicketImapEmailBlacklistDataProvider $ticketImapEmailBlacklistDataProvider
    ) {
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->ticketImapEmailBlacklistDataProvider = $ticketImapEmailBlacklistDataProvider;
    }

    public function create(): Grid
    {
        $qb = $this->ticketImapEmailBlacklistDataProvider->getGridModel();
        $grid = $this->gridFactory->createGrid($qb, __CLASS__, 'tieb_emailAddress', Grid::ASC);

        $grid->addIdentifier('tieb_id', 'tieb.id');
        $grid->attached();
        $grid->addTextColumn('tieb_emailAddress', 'tieb.emailAddress', 'Email address')->setSortable();

        $grid->addEditActionButton(
            'setting_ticketing_imap_email_blacklist_edit',
            [],
            SettingTicketingController::class,
            true
        );

        $grid->addDeleteActionButton(
            'setting_ticketing_imap_email_blacklist_delete',
            [],
            SettingTicketingController::class
        );

        return $grid;
    }
}
