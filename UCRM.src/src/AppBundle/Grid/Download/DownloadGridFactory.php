<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Grid\Download;

use AppBundle\Component\Grid\Column\BaseColumn;
use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\Controller\DownloadController;
use AppBundle\Entity\Download;
use AppBundle\Entity\User;
use AppBundle\Facade\DownloadFacade;
use AppBundle\Security\Permission;
use AppBundle\Util\Formatter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class DownloadGridFactory
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
     * @var Formatter
     */
    private $formatter;

    /**
     * @var DownloadFacade
     */
    private $downloadFacade;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        Formatter $formatter,
        DownloadFacade $downloadFacade,
        TokenStorageInterface $tokenStorage
    ) {
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->formatter = $formatter;
        $this->downloadFacade = $downloadFacade;
        $this->tokenStorage = $tokenStorage;
    }

    public function create(): Grid
    {
        $user = $this->tokenStorage->getToken()->getUser();
        assert($user instanceof User);
        $qb = $this->downloadFacade->getGridModel($user);

        $grid = $this->gridFactory->createGrid($qb, __CLASS__, 'd');

        $grid->addIdentifier('d_id', 'd.id');
        $grid->setRowUrl('download_download');
        $grid->setDefaultSort('d_created', Grid::DESC);

        $grid->attached();

        $grid->addTextColumn('d_name', 'd.name', 'Name')
            ->setSortable();

        $grid
            ->addCustomColumn(
                'd_state',
                'Status',
                function ($row) {
                    /** @var Download $download */
                    $download = $row[0];

                    $status = $this->gridHelper->trans(Download::STATUSES[$download->getStatus()]);
                    if ($description = $download->getStatusDescription()) {
                        return sprintf(
                            '%s (%s)',
                            $status,
                            $this->gridHelper->trans($description)
                        );
                    }

                    return $status;
                }
            );

        $grid
            ->addCustomColumn(
                'd_file',
                'File',
                function ($row) {
                    /** @var Download $download */
                    $download = $row[0];

                    return $download->getPath()
                        ? pathinfo($download->getPath(), PATHINFO_BASENAME)
                        : BaseColumn::EMPTY_COLUMN;
                }
            );

        $grid
            ->addTwigFilterColumn(
                'd_created',
                'd.created',
                'Date',
                'localizedDate',
                [Formatter::DEFAULT, Formatter::SHORT]
            )
            ->setSortable();

        $downloadButton = $grid->addActionButton(
            'download_download',
            [],
            DownloadController::class,
            Permission::VIEW
        );
        $downloadButton->setTitle('Download');
        $downloadButton->setCssClasses(['button--primary']);
        $downloadButton->addRenderCondition(
            function ($row) {
                /** @var Download $download */
                $download = $row[0];

                return $download->getStatus() === Download::STATUS_READY;
            }
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
            'Do you really want to delete these files?',
            null,
            'ucrm-icon--trash'
        );
        $deleteMultiAction->confirmOkay = 'Delete forever';

        return $grid;
    }

    private function multiDeleteAction(Grid $grid): RedirectResponse
    {
        $this->gridHelper->denyAccessUnlessPermissionGranted(Permission::EDIT, DownloadController::class);

        list($deleted, $failed) = $this->downloadFacade->handleDeleteMultiple($grid->getDoMultiActionIds());

        if ($deleted > 0) {
            $this->gridHelper->addTranslatedFlash(
                'success',
                'Deleted %count% files.',
                $deleted,
                [
                    '%count%' => $deleted,
                ]
            );
        }

        if ($failed > 0) {
            $this->gridHelper->addTranslatedFlash(
                'warning',
                '%count% files could not be deleted.',
                $failed,
                [
                    '%count%' => $failed,
                ]
            );
        }

        return new RedirectResponse($grid->generateMultiActionReturnUrl());
    }
}
