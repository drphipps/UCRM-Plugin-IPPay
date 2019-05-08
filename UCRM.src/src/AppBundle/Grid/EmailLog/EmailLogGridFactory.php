<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\EmailLog;

use AppBundle\Component\Grid\Grid;
use AppBundle\Controller\ClientController;
use AppBundle\Entity\Client;
use AppBundle\Entity\EmailLog;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\Mailing;
use AppBundle\Util\Formatter;

class EmailLogGridFactory extends BaseEmailLogGridFactory
{
    public function create(
        Invoice $invoice = null,
        Client $client = null,
        Mailing $mailing = null,
        Quote $quote = null
    ): Grid {
        $qb = $this->emailLogDataProvider->getQueryBuilder($invoice, $client, $mailing, $quote);

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

        if ($invoice) {
            $grid->addRouterUrlParam('id', $invoice->getId());
        }

        if ($client) {
            $grid->addRouterUrlParam('id', $client->getId());
            $grid->setRouterUrlSuffix('#tab-' . ClientController::EMAIL_LOG);
        }

        if ($mailing) {
            $grid->addRouterUrlParam('id', $mailing->getId());
        }

        if ($quote) {
            $grid->addRouterUrlParam('id', $quote->getId());
        }

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
            ->setCssClass('email-log__column__message');

        $grid->addRawCustomColumn(
            'l_recipient',
            'Recipient',
            function ($row) use ($mailing) {
                /** @var EmailLog $log */
                $log = $row[0];

                if ($mailing && ! $log->getRecipient()) {
                    return $log->getClient()
                        ? htmlspecialchars($log->getClient()->getNameForView() ?? '', ENT_QUOTES)
                        : '';
                }

                return $this->emailLogRenderer->renderRecipient($log);
            }
        );

        $grid->addTextColumn('l_subject', 'l.subject', 'Subject');

        $grid
            ->addTwigFilterColumn(
                'l_created_date',
                'l.createdDate',
                'Date',
                'localizedDate',
                [Formatter::DEFAULT, Formatter::MEDIUM]
            )
            ->setSortable();

        $resendButton = $grid->addActionButton('email_log_resend', ['mailing' => $mailing ? $mailing->getId() : null]);
        $resendButton->setCssClasses(['button--primary']);
        $resendButton->setTitle($this->gridHelper->trans('Resend email'));
        $resendButton->setIcon('ucrm-icon--sync');
        $resendButton->setData(
            [
                'confirm' => $this->translator->trans('Do you really want to resend this email?'),
                'confirm-title' => $this->translator->trans('Resend email'),
                'confirm-okay' => $this->translator->trans('Resend'),
            ]
        );
        $resendButton->addRenderCondition(
            function ($row) {
                /** @var EmailLog $entity */
                $entity = $row[0];

                return $entity->getStatus() === EmailLog::STATUS_ERROR
                    && ! empty(trim($entity->getRecipient()));
            }
        );

        $resendMultiAction = $grid->addMultiAction(
            'resend',
            'Resend failed emails',
            function () use ($grid) {
                return $this->multiResendAction($grid);
            },
            [
                'button--primary',
            ],
            null,
            null,
            'ucrm-icon--email'
        );
        $resendMultiAction->confirmMessage = 'Do you really want to resend failed emails?';
        $resendMultiAction->confirmTitle = 'Resend failed emails';
        $resendMultiAction->confirmOkay = 'Resend';

        return $grid;
    }
}
