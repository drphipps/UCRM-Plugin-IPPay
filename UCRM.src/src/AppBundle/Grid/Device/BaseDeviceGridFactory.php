<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Grid\Device;

use AppBundle\Component\Elastic;
use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\Controller\DeviceController;
use AppBundle\Entity\Device;
use AppBundle\Facade\DeviceFacade;
use AppBundle\Security\Permission;
use AppBundle\Util\Formatter;
use Symfony\Component\HttpFoundation\RedirectResponse;

abstract class BaseDeviceGridFactory
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
     * @var Formatter
     */
    protected $formatter;

    /**
     * @var DeviceFacade
     */
    protected $deviceFacade;

    /**
     * @var Elastic\Search
     */
    protected $elasticSearch;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    public function __construct(
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        Formatter $formatter,
        DeviceFacade $deviceFacade,
        Elastic\Search $elasticSearch,
        \Twig_Environment $twig
    ) {
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->formatter = $formatter;
        $this->deviceFacade = $deviceFacade;
        $this->elasticSearch = $elasticSearch;
        $this->twig = $twig;
    }

    protected function multiDeleteAction(Grid $grid): RedirectResponse
    {
        $this->gridHelper->denyAccessUnlessPermissionGranted(Permission::EDIT, DeviceController::class);

        list($deleted, $failed) = $this->deviceFacade->handleDeleteMultiple($grid->getDoMultiActionIds());

        if ($deleted > 0) {
            $this->gridHelper->addTranslatedFlash(
                'success',
                'Deleted %count% devices.',
                $deleted,
                [
                    '%count%' => $deleted,
                ]
            );
        }

        if ($failed > 0) {
            $this->gridHelper->addTranslatedFlash(
                'warning',
                '%count% devices could not be deleted.',
                $failed,
                [
                    '%count%' => $failed,
                ]
            );
        }

        return new RedirectResponse($grid->generateMultiActionReturnUrl());
    }

    protected function multiSyncAction(Grid $grid): RedirectResponse
    {
        $this->gridHelper->denyAccessUnlessPermissionGranted(Permission::VIEW, DeviceController::class);

        list($synced, $alreadySynced, $failed) = $this->deviceFacade->handleAddToSyncQueueMultiple(
            $grid->getDoMultiActionIds()
        );

        if ($synced > 0) {
            $this->gridHelper->addTranslatedFlash(
                'success',
                'Added %count% devices to sync queue.',
                $synced,
                [
                    '%count%' => $synced,
                ]
            );
        }

        if ($alreadySynced > 0) {
            $this->gridHelper->addTranslatedFlash(
                'info',
                '%count% devices already in sync queue.',
                $alreadySynced,
                [
                    '%count%' => $alreadySynced,
                ]
            );
        }

        if ($failed > 0) {
            $this->gridHelper->addTranslatedFlash(
                'warning',
                '%count% devices have wrong login information or no accessible IP.',
                $failed,
                [
                    '%count%' => $failed,
                ]
            );
        }

        return new RedirectResponse($grid->generateMultiActionReturnUrl());
    }

    protected function renderDeviceStatusBall(int $status, string $label): string
    {
        switch ($status) {
            case Device::STATUS_ONLINE:
                $type = 'success';
                break;
            case Device::STATUS_UNREACHABLE:
                $type = 'warning';
                break;
            case Device::STATUS_DOWN:
                $type = 'danger';
                break;
            case Device::STATUS_UNKNOWN:
            default:
                $type = '';
                break;
        }

        return $this->twig->render(
            'client/components/view/status_ball.html.twig',
            [
                'type' => $type,
                'label' => $label,
            ]
        );
    }
}
