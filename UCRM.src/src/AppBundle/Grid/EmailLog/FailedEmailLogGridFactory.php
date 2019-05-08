<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\EmailLog;

use AppBundle\Component\Grid\Button\ActionButton;
use AppBundle\Component\Grid\Grid;
use AppBundle\Entity\EmailLog;
use AppBundle\Util\Formatter;

class FailedEmailLogGridFactory extends BaseEmailLogGridFactory
{
    public function create(): Grid
    {
        $qb = $this->emailLogDataProvider->getFailedNotDiscardedGridModel();

        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->addIdentifier('l_id', 'l.id');
        $grid->setDefaultSort(null);

        $grid->setRowUrlIsModal();
        $grid->setRowUrl(
            'email_log_show',
            null,
            [
                'id' => 'l_id',
            ]
        );

        $grid->setRoute('homepage');
        $grid->attached();

        $grid
            ->addRawCustomColumn(
                'l_message',
                'Message',
                function ($row) {
                    /** @var EmailLog $log */
                    $log = $row[0];

                    return $this->emailLogRenderer->renderMessage($log, false, true);
                }
            )
            ->setCssClass('log__column__message');

        $grid
            ->addTwigFilterColumn(
                'l_date',
                'l.createdDate',
                'Date',
                'localizedDateToday',
                [Formatter::NONE, Formatter::MEDIUM, Formatter::DEFAULT, Formatter::MEDIUM]
            )
            ->setCssClass('log__column__date');

        $discardButton = $grid->addActionButton(
            'hide_failed_email',
            [
                'id' => 'l_id',
            ]
        );
        $discardButton->setIcon('ucrm-icon--archive');
        $discardButton->addRenderCondition(
            function ($row) {
                /** @var EmailLog|null $emailLog */
                $emailLog = $row[0];

                return $emailLog
                    ? ! $emailLog->isDiscarded() && $emailLog->getStatus() === EmailLog::STATUS_ERROR
                    : false;
            }
        );
        $discardButton->setCssClasses(
            [
                'action',
                'ajax',
            ],
            true
        );
        $discardButton->setData(
            [
                ActionButton::KEY_TOOLTIP => $this->gridHelper->trans('Hide from dashboard'),
                'confirm' => $this->gridHelper->trans('Do you really want to hide the email from dashboard?'),
                'confirm-title' => $this->gridHelper->trans('Hide from dashboard'),
                'confirm-okay' => $this->gridHelper->trans('Hide'),
            ]
        );

        $grid->setShowHeader(false);
        $grid->setShowFooter(false);
        $grid->setItemsPerPage(5);

        return $grid;
    }
}
