<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\Document;

use AppBundle\Component\Grid\Column\BaseColumn;
use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\Controller\DocumentController;
use AppBundle\DataProvider\DocumentDataProvider;
use AppBundle\Entity\Client;
use AppBundle\Entity\Document;
use AppBundle\Facade\DocumentFacade;
use AppBundle\Security\Permission;
use AppBundle\Service\DownloadResponseFactory;
use AppBundle\Util\Formatter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use ZipStream;

class DocumentGridFactory
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
     * @var DocumentFacade
     */
    private $documentFacade;

    /**
     * @var DownloadResponseFactory
     */
    private $downloadResponseFactory;

    /**
     * @var DocumentDataProvider
     */
    private $documentDataProvider;

    public function __construct(
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        DocumentFacade $documentFacade,
        DocumentDataProvider $documentDataProvider,
        DownloadResponseFactory $downloadResponseFactory
    ) {
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->documentFacade = $documentFacade;
        $this->documentDataProvider = $documentDataProvider;
        $this->downloadResponseFactory = $downloadResponseFactory;
    }

    public function create(Client $client, string $filterType): Grid
    {
        $qb = $this->documentDataProvider->getGridModel($client);
        if ($filterType !== DocumentController::FILTER_ALL) {
            $qb->andWhere('d.type = :filterType');
        }

        switch ($filterType) {
            case DocumentController::FILTER_DOCUMENTS:
                $qb->setParameter('filterType', Document::TYPE_DOCUMENT);
                break;
            case DocumentController::FILTER_IMAGES:
                $qb->setParameter('filterType', Document::TYPE_IMAGE);
                break;
            case DocumentController::FILTER_OTHERS:
                $qb->setParameter('filterType', Document::TYPE_OTHER);
                break;
        }

        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->setRowUrl('documents_download');
        $grid->addIdentifier('d_id', 'd.id');
        $grid->setDefaultSort('d_name', 'ASC');
        $grid->addRouterUrlParam('id', $client->getId());
        $grid->addRouterUrlParam('filterType', $filterType);

        $grid->attached();

        $grid->addTextColumn('d_name', 'd.name', 'Name')
            ->setSortable();
        $grid->addTextColumn('d_type', 'd.type', 'Type')
            ->setSortable();
        $grid
            ->addTwigFilterColumn(
                'd_created_date',
                'd.createdDate',
                'Date',
                'localizedDate',
                [Formatter::DEFAULT, Formatter::SHORT]
            )
            ->setSortable();
        $grid->addCustomColumn(
            'd_user',
            'User',
            function ($row) {
                /** @var Document $document */
                $document = $row[0];

                return $document->getUser()
                    ? $document->getUser()->getNameForView()
                    : BaseColumn::EMPTY_COLUMN;
            }
        );
        $grid->addTwigFilterColumn('d_size', 'd.size', 'Size', 'bytesToSize')
            ->setSortable();

        $grid->addMultiAction(
            'download',
            'Download in ZIP archive',
            function () use ($grid) {
                return $this->downloadZipAction($grid);
            },
            [],
            null,
            null,
            'ucrm-icon--download'
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
            'Do you really want to delete these documents?',
            null,
            'ucrm-icon--trash'
        );
        $deleteMultiAction->confirmOkay = 'Delete forever';

        return $grid;
    }

    private function multiDeleteAction(Grid $grid): RedirectResponse
    {
        $this->gridHelper->denyAccessUnlessPermissionGranted(Permission::EDIT, DocumentController::class);

        list($deleted, $failed) = $this->documentFacade->handleDeleteMultiple($grid->getDoMultiActionIds());

        if ($deleted > 0) {
            $this->gridHelper->addTranslatedFlash(
                'success',
                'Deleted %count% documents.',
                $deleted,
                [
                    '%count%' => $deleted,
                ]
            );
        }

        if ($failed > 0) {
            $this->gridHelper->addTranslatedFlash(
                'warning',
                '%count% documents could not be deleted.',
                $failed,
                [
                    '%count%' => $failed,
                ]
            );
        }

        return new RedirectResponse($grid->generateMultiActionReturnUrl());
    }

    private function downloadZipAction(Grid $grid): Response
    {
        $this->gridHelper->denyAccessUnlessPermissionGranted(Permission::VIEW, DocumentController::class);

        try {
            return $this->documentFacade->handleStreamedZipDownload($grid->getDoMultiActionIds());
        } catch (ZipStream\Exception $exception) {
            $this->gridHelper->addTranslatedFlash('danger', $this->gridHelper->trans($exception->getMessage()));

            return new RedirectResponse($grid->generateMultiActionReturnUrl());
        }
    }
}
