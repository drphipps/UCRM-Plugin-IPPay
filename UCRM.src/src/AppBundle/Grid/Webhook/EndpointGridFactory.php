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
use AppBundle\Controller\WebhookEndpointController;
use AppBundle\DataProvider\WebhookDataProvider;
use AppBundle\Facade\WebhookFacade;
use AppBundle\Security\Permission;
use Symfony\Component\HttpFoundation\RedirectResponse;

class EndpointGridFactory
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
     * @var WebhookFacade
     */
    private $webhookFacade;

    /**
     * @var WebhookDataProvider
     */
    private $webhookDataProvider;

    public function __construct(
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        WebhookFacade $webhookFacade,
        WebhookDataProvider $webhookDataProvider
    ) {
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->webhookFacade = $webhookFacade;
        $this->webhookDataProvider = $webhookDataProvider;
    }

    public function create(): Grid
    {
        $qb = $this->webhookDataProvider->getGridModel();

        $grid = $this->gridFactory->createGrid($qb, __CLASS__, 'wha_id');
        $grid->setRowUrl('webhook_endpoint_show');
        $grid->addIdentifier('wha_id', 'wha.id');
        $grid->setDefaultSort('wha_id');

        $grid->attached();

        $grid->addTextColumn(
            'wha_url',
            'wha.url',
            'Endpoint URL'
        );

        $grid
            ->addTwigFilterColumn(
                'wha_active',
                'wha.isActive',
                'Active',
                'yesNo'
            )
            ->setSortable();

        $button = $grid->addActionButton(
            'webhook_endpoint_test',
            [],
            WebhookEndpointController::class,
            Permission::EDIT
        );
        $button->setIcon('ucrm-icon--cog');
        $button->setData(
            [
                'tooltip' => $this->gridHelper->trans('Test endpoint'),
            ]
        );

        $grid->addEditActionButton('webhook_endpoint_edit', [], WebhookEndpointController::class);
        $grid->addDeleteActionButton('webhook_endpoint_delete', [], WebhookEndpointController::class);

        $grid->addMultiAction(
            'test',
            'Test endpoint',
            function () use ($grid) {
                return $this->multiTestSendAction($grid);
            },
            [],
            null,
            null,
            'ucrm-icon--cog'
        );

        $deleteMultiAction = $grid->addMultiAction(
            'delete',
            'Delete',
            function () use ($grid) {
                return $this->multiDeleteAction($grid);
            },
            [
                'button--danger',
            ],
            'Do you really want to delete these webhook endpoints?',
            null,
            'ucrm-icon--trash'
        );
        $deleteMultiAction->confirmOkay = 'Delete forever';

        return $grid;
    }

    private function multiDeleteAction(Grid $grid): RedirectResponse
    {
        $this->gridHelper->denyAccessUnlessPermissionGranted(Permission::EDIT, WebhookEndpointController::class);

        list($deleted, $failed) = $this->webhookFacade->handleDeleteMultiple($grid->getDoMultiActionIds());

        if ($deleted > 0) {
            $this->gridHelper->addTranslatedFlash(
                'success',
                'Deleted %count% webhook endpoints.',
                $deleted,
                [
                    '%count%' => $deleted,
                ]
            );
        }

        if ($failed > 0) {
            $this->gridHelper->addTranslatedFlash(
                'warning',
                '%count% webhook endpoints could not be deleted.',
                $failed,
                [
                    '%count%' => $failed,
                ]
            );
        }

        return new RedirectResponse($grid->generateMultiActionReturnUrl());
    }

    private function multiTestSendAction(Grid $grid): RedirectResponse
    {
        $this->gridHelper->denyAccessUnlessPermissionGranted(Permission::EDIT, WebhookEndpointController::class);

        list($sent, $failed) = $this->webhookFacade->handleTestSendMultiple($grid->getDoMultiActionIds());
        if ($sent > 0) {
            $this->gridHelper->addTranslatedFlash(
                'success',
                'Sent %count% webhook requests.',
                $sent,
                [
                    '%count%' => $sent,
                ]
            );
        }

        if ($failed > 0) {
            $this->gridHelper->addTranslatedFlash(
                'warning',
                '%count% webhook requests could not be sent.',
                $failed,
                [
                    '%count%' => $failed,
                ]
            );
        }

        return new RedirectResponse($grid->generateMultiActionReturnUrl());
    }
}
