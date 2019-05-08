<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\Quote;

use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\Controller\QuoteController;
use AppBundle\DataProvider\ClientDataProvider;
use AppBundle\DataProvider\QuoteDataProvider;
use AppBundle\Entity\User;
use AppBundle\Facade\OrganizationFacade;
use AppBundle\Facade\QuoteExportFacade;
use AppBundle\Facade\QuoteFacade;
use AppBundle\Security\Permission;
use AppBundle\Service\BadgeFactory;
use AppBundle\Service\DownloadResponseFactory;
use AppBundle\Util\Formatter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

abstract class BaseQuoteGridFactory
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
     * @var QuoteDataProvider
     */
    protected $quoteDataProvider;

    /**
     * @var QuoteFacade
     */
    protected $quoteFacade;

    /**
     * @var OrganizationFacade
     */
    protected $organizationFacade;

    /**
     * @var QuoteExportFacade
     */
    private $quoteExportFacade;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var DownloadResponseFactory
     */
    private $downloadResponseFactory;

    /**
     * @var BadgeFactory
     */
    protected $badgeFactory;

    /**
     * @var ClientDataProvider
     */
    protected $clientDataProvider;

    public function __construct(
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        Formatter $formatter,
        QuoteDataProvider $quoteDataProvider,
        QuoteFacade $quoteFacade,
        OrganizationFacade $organizationFacade,
        QuoteExportFacade $quoteExportFacade,
        TokenStorageInterface $tokenStorage,
        DownloadResponseFactory $downloadResponseFactory,
        BadgeFactory $badgeFactory,
        ClientDataProvider $clientDataProvider
    ) {
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->formatter = $formatter;
        $this->quoteDataProvider = $quoteDataProvider;
        $this->quoteFacade = $quoteFacade;
        $this->organizationFacade = $organizationFacade;
        $this->quoteExportFacade = $quoteExportFacade;
        $this->tokenStorage = $tokenStorage;
        $this->downloadResponseFactory = $downloadResponseFactory;
        $this->badgeFactory = $badgeFactory;
        $this->clientDataProvider = $clientDataProvider;
    }

    protected function multiDeleteAction(Grid $grid): RedirectResponse
    {
        $this->gridHelper->denyAccessUnlessPermissionGranted(Permission::EDIT, QuoteController::class);

        list($deleted, $failed) = $this->quoteFacade->handleDeleteMultipleIds($grid->getDoMultiActionIds());

        if ($deleted > 0) {
            $this->gridHelper->addTranslatedFlash(
                'success',
                'Deleted %count% quotes.',
                $deleted,
                [
                    '%count%' => $deleted,
                ]
            );
        }

        if ($failed > 0) {
            $this->gridHelper->addTranslatedFlash(
                'warning',
                '%count% quotes could not be deleted.',
                $failed,
                [
                    '%count%' => $failed,
                ]
            );
        }

        return new RedirectResponse($grid->generateMultiActionReturnUrl());
    }

    protected function exportPdfAction(Grid $grid): Response
    {
        $ids = $grid->getDoMultiActionIds() ?: $grid->getExportIds();
        $count = count($ids);

        if (1 === $count) {
            $pdf = $this->quoteExportFacade->getMergedPdf($ids);
            if (null === $pdf) {
                $this->gridHelper->addTranslatedFlash('warning', 'There are no quotes to export.');

                return new RedirectResponse($grid->generateMultiActionReturnUrl());
            }

            return $this->downloadResponseFactory->createFromContent($pdf, 'quotes', 'pdf', 'application/pdf');
        }

        if (! $ids) {
            $this->gridHelper->addTranslatedFlash('warning', 'There are no quotes to export.');

            return new RedirectResponse($grid->generateMultiActionReturnUrl());
        }

        $this->quoteExportFacade->preparePdfDownload(
            $this->gridHelper->transChoice(
                '%count% exported quotes',
                $count,
                [
                    '%count%' => $count,
                ]
            ),
            $ids,
            $this->getUser()
        );

        $this->gridHelper->addTranslatedFlash(
            'success',
            'Export was added to queue. You can download it in System > Tools > Downloads.',
            null,
            [
                '%link%' => $this->gridHelper->generateUrl('download_index'),
            ]
        );

        return new RedirectResponse($grid->generateMultiActionReturnUrl());
    }

    protected function exportPdfOverviewAction(Grid $grid): Response
    {
        $ids = $grid->getDoMultiActionIds() ?: $grid->getExportIds();
        if (! $ids) {
            $this->gridHelper->addTranslatedFlash('warning', 'There are no quotes to export.');

            return new RedirectResponse($grid->generateMultiActionReturnUrl());
        }

        $count = count($ids);
        $this->quoteExportFacade->preparePdfOverviewDownload(
            $this->gridHelper->transChoice(
                '%filetype% overview of %count% quotes',
                $count,
                [
                    '%count%' => $count,
                    '%filetype%' => 'PDF',
                ]
            ),
            $ids,
            $this->getUser()
        );

        $this->gridHelper->addTranslatedFlash(
            'success',
            'Export was added to queue. You can download it in System > Tools > Downloads.',
            null,
            [
                '%link%' => $this->gridHelper->generateUrl('download_index'),
            ]
        );

        return new RedirectResponse($grid->generateMultiActionReturnUrl());
    }

    protected function exportCsvOverviewAction(Grid $grid): Response
    {
        $ids = $grid->getDoMultiActionIds() ?: $grid->getExportIds();
        if (! $ids) {
            $this->gridHelper->addTranslatedFlash('warning', 'There are no quotes to export.');

            return new RedirectResponse($grid->generateMultiActionReturnUrl());
        }

        $count = count($ids);
        $this->quoteExportFacade->prepareCsvOverviewDownload(
            $this->gridHelper->transChoice(
                '%filetype% overview of %count% quotes',
                $count,
                [
                    '%count%' => $count,
                    '%filetype%' => 'CSV',
                ]
            ),
            $ids,
            $this->getUser()
        );

        $this->gridHelper->addTranslatedFlash(
            'success',
            'Export was added to queue. You can download it in System > Tools > Downloads.',
            null,
            [
                '%link%' => $this->gridHelper->generateUrl('download_index'),
            ]
        );

        return new RedirectResponse($grid->generateMultiActionReturnUrl());
    }

    private function getUser(): User
    {
        $user = $this->tokenStorage->getToken()->getUser();
        assert($user instanceof User);

        return $user;
    }
}
