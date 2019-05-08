<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\EmailLog;

use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\Controller\EmailLogController;
use AppBundle\DataProvider\EmailLogDataProvider;
use AppBundle\RabbitMq\Email\ResendEmailsMessage;
use AppBundle\Security\Permission;
use AppBundle\Service\EmailLog\EmailLogRenderer;
use RabbitMqBundle\RabbitMqEnqueuer;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Translation\TranslatorInterface;

abstract class BaseEmailLogGridFactory
{
    /**
     * @var GridFactory
     */
    protected $gridFactory;

    /**
     * @var GridHelper
     */
    protected $gridHelper;

    /**
     * @var EmailLogDataProvider
     */
    protected $emailLogDataProvider;

    /**
     * @var EmailLogRenderer
     */
    protected $emailLogRenderer;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    public function __construct(
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        EmailLogDataProvider $emailLogDataProvider,
        EmailLogRenderer $emailLogRenderer,
        TranslatorInterface $translator,
        RabbitMqEnqueuer $rabbitMqEnqueuer
    ) {
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->emailLogDataProvider = $emailLogDataProvider;
        $this->emailLogRenderer = $emailLogRenderer;
        $this->translator = $translator;
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;
    }

    protected function multiResendAction(Grid $grid): RedirectResponse
    {
        $this->gridHelper->denyAccessUnlessPermissionGranted(Permission::EDIT, EmailLogController::class);

        $ids = $grid->getDoMultiActionIds();

        if ($ids) {
            $this->rabbitMqEnqueuer->enqueue(new ResendEmailsMessage($ids));

            $this->gridHelper->addTranslatedFlash(
                'success',
                '%count% emails have been added to the send queue.',
                count($ids),
                [
                    '%count%' => count($ids),
                ]
            );
        } else {
            $this->gridHelper->addTranslatedFlash(
                'warning',
                'No emails to resend.'
            );
        }

        return new RedirectResponse($grid->generateMultiActionReturnUrl());
    }
}
