<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\Mailing;

use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\DataProvider\MailingDataProvider;
use AppBundle\Util\Formatter;

class MailingGridFactory
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
     * @var MailingDataProvider
     */
    private $mailingDataProvider;

    public function __construct(
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        MailingDataProvider $mailingDataProvider
    ) {
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->mailingDataProvider = $mailingDataProvider;
    }

    public function create(): Grid
    {
        $qb = $this->mailingDataProvider->getMailingGridModel();
        $grid = $this->gridFactory->createGrid($qb, __CLASS__);

        $grid->addIdentifier('m_id', 'm.id');
        $grid->setRowUrl('mailing_show');

        $grid->attached();

        $grid
            ->addTwigFilterColumn(
                'm_created_date',
                'm.createdDate',
                'Created date',
                'localizedDate',
                [Formatter::DEFAULT, Formatter::SHORT]
            )
            ->setSortable();

        $grid
            ->addTwigFilterColumn(
                'm_subject',
                'm.subject',
                'Subject',
                'truncate',
                [50]
            )
            ->setSortable();

        $grid
            ->addTextColumn(
                'm_recipient_count',
                'count(DISTINCT el.client)',
                'Recipient count'
            )
            ->setSortable();

        return $grid;
    }
}
