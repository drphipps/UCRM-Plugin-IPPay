<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\Webhook;

use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\Controller\WebhookLogController;
use AppBundle\DataProvider\WebhookDataProvider;
use AppBundle\Entity\WebhookAddress;
use AppBundle\Facade\WebhookFacade;
use AppBundle\Security\Permission;
use AppBundle\Util\Formatter;
use Symfony\Component\HttpFoundation\RedirectResponse;

class EventGridFactory
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
     * @var WebhookDataProvider
     */
    private $webhookDataProvider;

    /**
     * @var WebhookFacade
     */
    private $webhookFacade;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    public function __construct(
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        WebhookFacade $webhookFacade,
        WebhookDataProvider $webhookDataProvider,
        \Twig_Environment $twig
    ) {
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->webhookFacade = $webhookFacade;
        $this->webhookDataProvider = $webhookDataProvider;
        $this->twig = $twig;
    }

    public function create(
        WebhookAddress $webhookAddress = null
    ): Grid {
        $qb = $this->webhookDataProvider->getLogGridModel($webhookAddress);

        $grid = $this->gridFactory->createGrid($qb, __CLASS__, 'whe_id');
        $grid->addIdentifier('whe_id', 'whe.id');
        $grid->setRowUrl('webhook_log_show');

        if ($webhookAddress) {
            $grid->addRouterUrlParam('id', $webhookAddress->getId());
        }

        $grid->attached();

        $grid
            ->addTwigFilterColumn(
                'whe_created_date',
                'whe.createdDate',
                'Event date',
                'localizedDate',
                [Formatter::DEFAULT, Formatter::SHORT]
            )
            ->setSortable();

        $grid
            ->addTextColumn(
                'whe_change_type',
                'whe.changeType',
                'Change type'
            )
            ->setSortable();

        $grid
            ->addTextColumn(
                'whe_entity',
                'whe.entity',
                'Entity'
            )
            ->setSortable();
        $grid
            ->addTextColumn(
                'whe_entity_id',
                'whe.entityId',
                'EntityID'
            )
            ->setSortable();

        $grid
            ->addRawCustomColumn(
                'response_code',
                'Response',
                function ($row) {
                    return $this->renderStatusBall($row['response_code'] ?? '');
                }
            )
            ->setSortable();

        $button = $grid->addActionButton(
            'webhook_log_resend',
            [],
            WebhookLogController::class,
            Permission::EDIT
        );
        $button->setIcon('ucrm-icon--sync');
        $button->setData(
            [
                'tooltip' => $this->gridHelper->trans('Resend'),
            ]
        );

        $grid->addMultiAction(
            'resend',
            'Resend',
            function () use ($grid) {
                return $this->multiResendAction($grid);
            },
            [],
            'Do you wish to resend this event?',
            null,
            'ucrm-icon--sync'
        );

        return $grid;
    }

    private function renderStatusBall(string $response): string
    {
        $responses = array_unique(explode(',', $response));
        $type = ! in_array('200', $responses, true)
            ? 'danger'
            : (
            count($responses) === 1
                ? 'success'
                : 'warning'
            );

        return $this->twig->render(
            'webhook/components/view/status_ball.html.twig',
            [
                'type' => $type,
                'title' => $response,
            ]
        );
    }

    private function multiResendAction(Grid $grid): RedirectResponse
    {
        $this->gridHelper->denyAccessUnlessPermissionGranted(Permission::EDIT, WebhookLogController::class);

        list($sent, $failed) = $this->webhookFacade->handleResendMultiple($grid->getDoMultiActionIds());
        if ($sent > 0) {
            $this->gridHelper->addTranslatedFlash(
                'success',
                'Resent %count% webhook requests.',
                $sent,
                [
                    '%count%' => $sent,
                ]
            );
        }

        if ($failed > 0) {
            $this->gridHelper->addTranslatedFlash(
                'warning',
                '%count% webhook requests could not be resent.',
                $failed,
                [
                    '%count%' => $failed,
                ]
            );
        }

        return new RedirectResponse($grid->generateMultiActionReturnUrl());
    }
}
