<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\Settings;

use AppBundle\Component\Grid\Column\BaseColumn;
use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\Controller\SettingTicketingController;
use AppBundle\Security\Permission;
use Nette\Utils\Html;
use TicketingBundle\DataProvider\TicketImapInboxDataProvider;

class TicketingImapInboxGridFactory
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
     * @var TicketImapInboxDataProvider
     */
    private $ticketImapInboxDataProvider;

    public function __construct(
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        TicketImapInboxDataProvider $ticketImapInboxDataProvider
    ) {
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->ticketImapInboxDataProvider = $ticketImapInboxDataProvider;
    }

    public function create(): Grid
    {
        $qb = $this->ticketImapInboxDataProvider->getGridModel();
        $grid = $this->gridFactory->createGrid($qb, __CLASS__, 'tii_server_name', Grid::ASC);

        $grid->addIdentifier('tii_id', 'tii.id');
        $grid->attached();

        $grid->addTwigFilterColumn('tii_isDefault', 'tii.isDefault', 'Default', 'yesNo')
            ->setSortable();

        $grid->addTwigFilterColumn('tii_enabled', 'tii.enabled', 'Import enabled', 'yesNo')
            ->setSortable();

        $grid->addTextColumn('tii_server_name', 'tii.serverName', 'Server name')->setSortable();

        $grid->addTextColumn('tii_emailAddress', 'tii.emailAddress', 'Email address')->setSortable();

        $grid
            ->addCustomColumn(
                'tg_ticketGroupName',
                'Ticket group name',
                function ($row) {
                    return $row['tg_ticketGroupName'] ?: BaseColumn::EMPTY_COLUMN;
                }
            )
            ->setSortable();

        $grid
            ->addRawCustomColumn(
                'tg_checkConnection',
                'Validate',
                function ($row) {
                    return Html::el('a')
                        ->setAttribute('href', '#')
                        ->setAttribute('class', 'inbox-checker')
                        ->setAttribute(
                            'data-inbox-check-url',
                            $this->gridHelper->generateUrl(
                                'system_status_check_imap_inbox',
                                [
                                    'id' => $row['tii_id'],
                                ]
                            )
                        )
                        ->setText($this->gridHelper->trans('test connection'));
                }
            );

        $grid->addEditActionButton('setting_ticketing_imap_inbox_edit', [], SettingTicketingController::class, true);
        $dateStartButton = $grid->addActionButton(
            'setting_ticketing_imap_inbox_configuration',
            [],
            SettingTicketingController::class,
            Permission::EDIT
        );
        $dateStartButton->setIcon('ucrm-icon--cog');
        $dateStartButton->setData(
            [
                'tooltip' => $this->gridHelper->trans('Configuration'),
            ]
        );
        $dateStartButton->setIsModal();

        $deleteButton = $grid->addDeleteActionButton(
            'setting_ticketing_imap_inbox_delete',
            [],
            SettingTicketingController::class
        );
        $deleteButton->setData(
            [
                'confirm' => sprintf(
                    '<div class="verticalRhythmHalf">%s</div><div class="warning"><small>%s</small></div>',
                    $this->gridHelper->trans('Do you really want to delete this inbox?'),
                    $this->gridHelper->trans(
                        'Downloading attachments and displaying original message will be disabled for tickets imported from this inbox.'
                    )
                ),
                'confirm-okay' => $this->gridHelper->trans('Delete'),
                'confirm-title' => $this->gridHelper->trans('Delete inbox'),
            ]
        );

        return $grid;
    }
}
